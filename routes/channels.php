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

// Quote channel — only the quote owner can listen
Broadcast::channel('quote.{quoteId}', function ($user, $quoteId) {
    return \App\Models\Quote::where('id', $quoteId)
        ->where('user_id', $user->id)
        ->exists();
});


Broadcast::channel('test.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Admin orders channel - only staff can listen
Broadcast::channel('admin.orders', function ($user) {
    return $user->hasRole(['admin', 'staff', 'super-admin']);
});
