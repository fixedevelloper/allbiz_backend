<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'amount' => (float) $this->amount,
            'amount_rest' => (float) $this->amount_rest,
            'operator' => $this->operator,
            'reference_id' => $this->reference_id,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

            // 🔹 Items liés à la commande
            'items' => $this->whenLoaded('items', fn () =>
        $this->items->map(fn ($item) => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $item->product->name ?? $item->name,
            'price' => (float) $item->price,
            'promotion_price' => $item->product->is_promotion
                ? (float) $item->product->promotion_price
                : null,
            'quantity' => $item->quantity,
            'image' => $item->product->images->first()?->src ?? null,
        'final_price' => $item->product->is_promotion
        ? (float) $item->product->promotion_price
        : (float) $item->price,
                ])
            ),

            // 🧾 Meta (optionnel)
            'meta' => $this->meta ? json_decode($this->meta, true) : null,
        ];
    }
}
