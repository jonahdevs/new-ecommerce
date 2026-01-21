<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'expires_at',
    ];

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    /**
     * Summary of items
     */

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Summary of user
     * @return BelongsTo<User, Cart>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
