<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => (int) $this->quantity,
            'subtotal' => $this->whenLoaded('product', fn () => (float) $this->product->price * $this->quantity),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}