<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Subscription\SubscriptionSubscription;

class SubscriptionSubscriber extends Model
{
    use HasFactory;

    protected $table = 'subscription_subscribers';



    protected $fillable = [
        'name',
        'email',
        'phone'
    ];

    protected $casts = [
        'id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    public array $searchable = [
        'name',
        'email',
        'phone'
    ];


    public array $sortable = [
        'id',
        'created_at',
        'updated_at'
    ];



    public function subscriptionSubscriptions(): HasMany
    {
        return $this->hasMany(SubscriptionSubscription::class, 'subscriber_id', 'id');
    }
}