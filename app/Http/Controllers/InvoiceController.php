<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    /**
     * GET /api/invoices
     * Paginated list with optional customer search.
     */
    #[OA\Get(
        path: '/api/invoices',
        summary: 'List invoices (paginated)',
        description: 'Returns a paginated list of invoices with optional Arabic/UTF-8 customer name search.',
        tags: ['Invoices'],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'query',
                description: 'Filter invoices by customer name (partial match, supports Arabic)',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'الحميري')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Items per page (max 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of invoices',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Invoice')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Invoice::with('items')->latest();

        if ($request->filled('customer')) {
            $query->where('customer', 'like', '%' . $request->input('customer') . '%');
        }

        return InvoiceResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/invoices
     */
    #[OA\Post(
        path: '/api/invoices',
        summary: 'Create a new invoice',
        description: 'Creates an invoice and its line items inside a database transaction. Server calculates subtotal, 15% tax, and total in YER; any client-supplied totals are ignored. Supports X-Idempotency-Key header to prevent duplicate submissions.',
        tags: ['Invoices'],
        parameters: [
            new OA\Parameter(
                name: 'X-Idempotency-Key',
                in: 'header',
                description: 'Optional unique key (e.g. UUID v4) to prevent duplicate invoice creation on double-tap or retries',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer', 'items'],
                properties: [
                    new OA\Property(property: 'customer', type: 'string', maxLength: 255, example: 'محمد الحميري'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(
                            required: ['name', 'qty', 'price'],
                            properties: [
                                new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'رز بسمتي 10 كيلو'),
                                new OA\Property(property: 'qty', type: 'integer', minimum: 1, example: 1),
                                new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0.01, example: 18000),
                            ]
                        ),
                        example: [
                            ['name' => 'رز بسمتي 10 كيلو', 'qty' => 1, 'price' => 18000],
                            ['name' => 'سكر 5 كيلو', 'qty' => 2, 'price' => 6500],
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Invoice created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Invoice'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The customer field is required.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function store(StoreInvoiceRequest $request)
    {
        $key = $request->header('X-Idempotency-Key');
        $key = is_string($key) && trim($key) !== '' ? trim($key) : null;

        if ($key !== null) {
            $existing = Invoice::where('idempotency_key', $key)->first();
            if ($existing) {
                return new InvoiceResource($existing->load('items'));
            }
        }

        $invoice = $this->invoiceService->create(
            $request->validatedInvoiceData(),
            $key,
        );

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/invoices/{id}
     */
    #[OA\Get(
        path: '/api/invoices/{id}',
        summary: 'Get invoice by ID',
        description: 'Returns a single invoice with its line items and server-calculated totals.',
        tags: ['Invoices'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Invoice ID',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invoice found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Invoice'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Invoice not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invoice not found.'),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id)
    {
        $invoice = Invoice::with('items')->find($id);

        if (! $invoice) {
            return response()->json([
                'message' => 'Invoice not found.',
            ], 404);
        }

        return new InvoiceResource($invoice);
    }
}
