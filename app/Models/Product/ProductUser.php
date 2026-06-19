<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUser extends Model
{
    use HasFactory;

    protected $table = 'product_users';


    public $timestamps = false;


    protected $fillable = [
        'name',
        'email',
        'phone',
        'is_active'
    ];

    protected $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
    ];


    public array $searchable = [
        'name',
        'email',
        'phone'
    ];


    public array $sortable = [
        'id',
        'created_at',
        'updated_at',
        'is_active'
    ];


}