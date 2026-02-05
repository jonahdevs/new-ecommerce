<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'phone_number_verified_at',
        'avatar',
        'is_active',
        'newsletter_subscribed',
        'default_payment_method',
        'preferred_shipping_method_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }


    // ===============================================
    // RELATIONSHIPS
    // ===============================================
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get all products in the user's wishlist
     */
    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlist_items', 'user_id', 'product_id')->withTimestamps();
    }

    public function recentlyViewedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'recently_viewed_products',
            'user_id',
            'product_id'
        )
            ->withPivot('viewed_at')
            ->withTimestamps();
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)
            ->where('is_default', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function preferredShippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'preferred_shipping_method_id');
    }
}
