<?php

use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    // Only the order owner can listen to this channel
    return Order::where('id', $orderId)
        ->where('user_id', $user->id)
        ->exists();
});
