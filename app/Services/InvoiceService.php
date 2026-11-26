<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Tax rate: 15 %.
     */
    public const TAX_RATE = '0.15';

    /**
     * Currency code.
     */
    public const CURRENCY = 'YER';

    /**
     * Create an invoice with its items inside a DB transaction.
     *
     * @param  array{customer: string, items: array<int, array{name: string, qty: int, price: numeric-string|int|float}>}  $data
     */
    public function create(array $data, ?string $idempotencyKey = null): Invoice
    {
        try {
            return DB::transaction(function () use ($data, $idempotencyKey) {
                // ── Build line items & compute subtotal ──────────────────
                $subtotal = '0';
                $lineItems = [];

                foreach ($data['items'] as $item) {
                    // Use bcmath for precise arithmetic (all values as strings).
                    $qty       = (string) $item['qty'];
                    $price     = (string) $item['price'];
                    $lineTotal = bcmul($qty, $price, 2);

                    $subtotal = bcadd($subtotal, $lineTotal, 2);

                    $lineItems[] = [
                        'name'       => $item['name'],
                        'qty'        => $item['qty'],
                        'price'      => $price,
                        'line_total' => $lineTotal,
                    ];
                }

                // ── Compute tax and total ───────────────────────────────
                $tax   = bcmul($subtotal, self::TAX_RATE, 2);
                $total = bcadd($subtotal, $tax, 2);

                // ── Persist invoice ─────────────────────────────────────
                $invoice = Invoice::create([
                    'customer'        => $data['customer'],
                    'subtotal'        => $subtotal,
                    'tax'             => $tax,
                    'total'           => $total,
                    'currency'        => self::CURRENCY,
                    'idempotency_key' => $idempotencyKey,
                ]);

                // ── Persist items ───────────────────────────────────────
                foreach ($lineItems as $item) {
                    $invoice->items()->create($item);
                }

                return $invoice->load('items');
            });
        } catch (UniqueConstraintViolationException $e) {
            // Last-resort race guard: another concurrent request inserted this key first.
            if ($idempotencyKey !== null) {
                $existing = Invoice::where('idempotency_key', $idempotencyKey)
                    ->with('items')
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }
    }
}
