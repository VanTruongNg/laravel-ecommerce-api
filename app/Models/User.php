<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';
}

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'role',
        'avatar_url',
        'email_verified_at',
        'phone',
        'address'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Kiểm tra người dùng có vai trò cụ thể
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Kiểm tra người dùng có phải admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN);
    }

    /**
     * Kiểm tra người dùng có phải khách hàng
     */
    public function isCustomer(): bool
    {
        return $this->hasRole(UserRole::CUSTOMER);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }
}