<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    public const STATUSES = [
        'pending',
        'processing',
        'delivering',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'total_price',
        'payment_method',
        'recipient_name',
        'phone',
        'address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Batalkan pesanan dan kembalikan stok produk.
     */
    public function cancelAndRestock(): void
    {
        $this->loadMissing('items');

        DB::transaction(function () {
            foreach ($this->items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }
            }

            $this->update(['status' => 'cancelled']);
        });
    }
}