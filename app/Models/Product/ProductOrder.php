<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\ProductProduct;
use App\Models\Product\ProductUser;

class ProductOrder extends Model
{
    use HasFactory;

    protected $table = 'product_orders';


    public $timestamps = false;


    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'order_date',
        'status'
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'order_date' => 'date',
    ];


    public array $searchable = [
        'status'
    ];


    public array $sortable = [
        'id',
        'created_at',
        'updated_at',
        'user_id',
        'product_id',
        'quantity',
        'order_date'
    ];



    public function user(): BelongsTo
    {
        return $this->belongsTo(ProductUser::class, 'user_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProduct::class, 'product_id', 'id');
    }
}