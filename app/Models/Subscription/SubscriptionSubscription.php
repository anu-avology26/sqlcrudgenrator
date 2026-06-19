<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\SubscriptionSubscriber;

class SubscriptionSubscription extends Model
{
    use HasFactory;

    protected $table = 'subscription_subscriptions';



    protected $fillable = [
        'subscriber_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',
        'name'
    ];

    protected $casts = [
        'id' => 'integer',
        'subscriber_id' => 'integer',
        'plan_id' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    public array $searchable = [
        'status',
        'name'
    ];


    public array $sortable = [
        'id',
        'created_at',
        'updated_at',
        'subscriber_id',
        'plan_id',
        'starts_at',
        'ends_at'
    ];



    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(SubscriptionSubscriber::class, 'subscriber_id', 'id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id', 'id');
    }
}