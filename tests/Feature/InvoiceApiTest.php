<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    // Valid Invoice Creation
    // ─────────────────────────────────────────────────────────────

    public function test_create_invoice_case_1_two_items(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'محمد الحميري',
            'items' => [
                ['name' => 'رز بسمتي 10 كيلو', 'qty' => 1, 'price' => 18000],
                ['name' => 'سكر 5 كيلو', 'qty' => 2, 'price' => 6500],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer', 'محمد الحميري')
            ->assertJsonPath('data.currency', 'YER')
            ->assertJsonPath('data.subtotal', 31000)
            ->assertJsonPath('data.tax', 4650)
            ->assertJsonPath('data.total', 35650)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_create_invoice_case_2_single_item(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'فاطمة العريقي',
            'items' => [
                ['name' => 'شاهي 450 جرام', 'qty' => 3, 'price' => 3100],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer', 'فاطمة العريقي')
            ->assertJsonPath('data.subtotal', 9300)
            ->assertJsonPath('data.tax', 1395)
            ->assertJsonPath('data.total', 10695)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_create_invoice_case_3_three_items(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'عبدالله الشامي',
            'items' => [
                ['name' => 'عسل سدر 500 جرام', 'qty' => 1, 'price' => 22000],
                ['name' => 'قهوة يمنية 250 جرام', 'qty' => 2, 'price' => 5200],
                ['name' => 'تونة (4 علب)', 'qty' => 1, 'price' => 3600],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer', 'عبدالله الشامي')
            ->assertJsonPath('data.subtotal', 36000)
            ->assertJsonPath('data.tax', 5400)
            ->assertJsonPath('data.total', 41400)
            ->assertJsonCount(3, 'data.items');
    }

    // ─────────────────────────────────────────────────────────────
    // Validation / Edge Cases
    // ─────────────────────────────────────────────────────────────

    public function test_reject_negative_quantity(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'سارة المقطري',
            'items' => [
                ['name' => 'دقيق 10 كيلو', 'qty' => -2, 'price' => 9500],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.qty'])
            ->assertJson([
                'errors' => [
                    'items.0.qty' => ['Item quantity must be at least 1 (zero or negative values are not allowed).'],
                ],
            ]);
    }

    public function test_reject_null_quantity(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'سارة المقطري',
            'items' => [
                ['name' => 'دقيق 10 كيلو', 'qty' => null, 'price' => 9500],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.qty'])
            ->assertJson([
                'errors' => [
                    'items.0.qty' => ['Item quantity is required and cannot be null.'],
                ],
            ]);
    }

    public function test_reject_null_price(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'أحمد الصبري',
            'items' => [
                ['name' => 'زيت طبخ 1.8 لتر', 'qty' => 1, 'price' => null],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.price'])
            ->assertJson([
                'errors' => [
                    'items.0.price' => ['Item price is required and cannot be null.'],
                ],
            ]);
    }

    public function test_reject_zero_price(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'أحمد الصبري',
            'items' => [
                ['name' => 'زيت طبخ 1.8 لتر', 'qty' => 1, 'price' => 0],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.price'])
            ->assertJson([
                'errors' => [
                    'items.0.price' => ['Item price must be greater than 0 (zero or negative values are not allowed).'],
                ],
            ]);
    }

    public function test_reject_missing_price(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'أحمد الصبري',
            'items' => [
                ['name' => 'زيت طبخ 1.8 لتر', 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.price']);
    }

    public function test_reject_empty_items_array(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'نورة الأغبري',
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_reject_missing_customer(): void
    {
        $response = $this->postJson('/api/invoices', [
            'items' => [
                ['name' => 'رز بسمتي', 'qty' => 1, 'price' => 5000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer']);
    }

    public function test_reject_missing_item_name(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'علي',
            'items' => [
                ['qty' => 1, 'price' => 5000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.name']);
    }

    public function test_reject_zero_quantity(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'علي',
            'items' => [
                ['name' => 'بيض', 'qty' => 0, 'price' => 2000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.qty']);
    }

    public function test_reject_negative_price(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'علي',
            'items' => [
                ['name' => 'بيض', 'qty' => 1, 'price' => -500],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.price']);
    }

    public function test_reject_missing_items_field(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'علي',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_client_sent_total_is_ignored(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer' => 'خالد العولقي',
            'total' => 1,
            'items' => [
                ['name' => 'حليب نيدو 900 جرام', 'qty' => 1, 'price' => 9800],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.subtotal', 9800)
            ->assertJsonPath('data.tax', 1470)
            ->assertJsonPath('data.total', 11270);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/invoices/{id}
    // ─────────────────────────────────────────────────────────────

    public function test_validation_returns_422_json_even_without_accept_header(): void
    {
        $response = $this->post('/api/invoices', [
            'customer' => 'محمد الحميري',
            'items' => [
                ['name' => 'رز بسمتي 10 كيلو', 'qty' => -1, 'price' => 18000],
                ['name' => 'سكر 5 كيلو', 'qty' => 2, 'price' => 6500],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.qty'])
            ->assertJson([
                'errors' => [
                    'items.0.qty' => ['Item quantity must be at least 1 (zero or negative values are not allowed).'],
                ],
            ]);
    }

    public function test_show_invoice_returns_200(): void
    {
        // Create an invoice first.
        $create = $this->postJson('/api/invoices', [
            'customer' => 'فاطمة العريقي',
            'items' => [
                ['name' => 'شاهي 450 جرام', 'qty' => 3, 'price' => 3100],
            ],
        ]);

        $id = $create->json('data.id');

        $response = $this->getJson("/api/invoices/{$id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.customer', 'فاطمة العريقي')
            ->assertJsonPath('data.subtotal', 9300)
            ->assertJsonPath('data.tax', 1395)
            ->assertJsonPath('data.total', 10695)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_show_invoice_returns_404_for_missing(): void
    {
        $response = $this->getJson('/api/invoices/99999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Invoice not found.');
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/invoices (List / Pagination / Search)
    // ─────────────────────────────────────────────────────────────

    public function test_list_invoices_paginated(): void
    {
        // Create a few invoices.
        foreach (['أحمد', 'سارة', 'محمد'] as $name) {
            $this->postJson('/api/invoices', [
                'customer' => $name,
                'items' => [
                    ['name' => 'بند', 'qty' => 1, 'price' => 1000],
                ],
            ]);
        }

        $response = $this->getJson('/api/invoices?per_page=2&page=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_list_invoices_filter_by_arabic_customer(): void
    {
        $this->postJson('/api/invoices', [
            'customer' => 'محمد الحميري',
            'items' => [['name' => 'رز', 'qty' => 1, 'price' => 5000]],
        ]);
        $this->postJson('/api/invoices', [
            'customer' => 'فاطمة العريقي',
            'items' => [['name' => 'شاهي', 'qty' => 1, 'price' => 3000]],
        ]);

        $response = $this->getJson('/api/invoices?customer=الحميري');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer', 'محمد الحميري');
    }

    public function test_list_invoices_returns_empty_for_no_match(): void
    {
        $this->postJson('/api/invoices', [
            'customer' => 'محمد',
            'items' => [['name' => 'رز', 'qty' => 1, 'price' => 5000]],
        ]);

        $response = $this->getJson('/api/invoices?customer=غير_موجود');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
