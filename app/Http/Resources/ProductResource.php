<?php

namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,

            'price' => (float) $this->price,
            'promotion_price' => $this->is_promotion
                ? (float) $this->promotion_price
                : null,

            'is_promotion' => (bool) $this->is_promotion,
            'downloable' => (bool) $this->downloable,

            // 🗂 Catégorie
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
            'name' => $this->category?->name,
            ]),

            // 🖼 Images
            'images' => $this->whenLoaded('images', fn () =>
    $this->images->map(fn ($img) => [
        'id' => $img->id,
        'name' => $img->name,
        'src' => $img->src,
        'url' => config('app.url') . $img->src,
    ])
    ),

            // 💰 Prix final calculé (UX front)
            'final_price' => $this->is_promotion
        ? (float) $this->promotion_price
        : (float) $this->price,

            // 🕒 Dates
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
