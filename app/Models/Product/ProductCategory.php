<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'product_categories';


    public $timestamps = false;


    protected $fillable = [
        'name',
        'status'
    ];

    protected $casts = [
        'id' => 'integer',
    ];


    public array $searchable = [
        'name',
        'status'
    ];


    public array $sortable = [
        'id',
        'created_at',
        'updated_at'
    ];


}