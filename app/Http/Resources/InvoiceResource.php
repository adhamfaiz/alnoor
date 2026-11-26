<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Invoice',
    title: 'Invoice',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'customer', type: 'string', example: 'محمد الحميري'),
        new OA\Property(property: 'currency', type: 'string', example: 'YER'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 31000),
        new OA\Property(property: 'tax', type: 'number', format: 'float', example: 4650),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 35650),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/InvoiceItem')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-26T12:30:00+00:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-26T12:30:00+00:00'),
    ]
)]
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'customer'   => $this->customer,
            'currency'   => $this->currency,
            'items'      => InvoiceItemResource::collection($this->whenLoaded('items')),
            'total'      => (float) $this->total,
            'tax'        => (float) $this->tax,
            'subtotal'   => (float) $this->subtotal,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
