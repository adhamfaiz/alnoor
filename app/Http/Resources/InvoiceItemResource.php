<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InvoiceItem',
    title: 'InvoiceItem',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'رز بسمتي 10 كيلو'),
        new OA\Property(property: 'qty', type: 'integer', example: 1),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 18000),
        new OA\Property(property: 'line_total', type: 'number', format: 'float', example: 18000),
    ]
)]
class InvoiceItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name'       => $this->name,
            'qty'        => (int) $this->qty,
            'price'      => (float) $this->price,
            'line_total' => (float) $this->line_total,
        ];
    }
}
