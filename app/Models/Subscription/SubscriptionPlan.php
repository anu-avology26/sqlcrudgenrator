<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Subscription\SubscriptionSubscription;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'subscription_plans';



    protected $fillable = [
        'name',
        'code',
        'price',
        'billing_cycle',
        'is_active'
    ];

    protected $casts = [
        'id' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    public array $searchable = [
        'name',
        'code'
    ];


    public array $sortable = [
        'id',
        'created_at',
        'updated_at',
        'price'
    ];



    public function subscriptionSubscriptions(): HasMany
    {
        return $this->hasMany(SubscriptionSubscription::class, 'plan_id', 'id');
    }
}