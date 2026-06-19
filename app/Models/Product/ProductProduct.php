<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\ProductCategory;

class ProductProduct extends Model
{
    use HasFactory;

    protected $table = 'product_products';


    public $timestamps = false;


    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'price',
        'is_active'
    ];

    protected $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];


    public array $searchable = [
        'name'
    ];


    public array $sortable = [
        'id',
        'created_at',
        'updated_at',
        'category_id',
        'price',
        'is_active'
    ];



    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }
}