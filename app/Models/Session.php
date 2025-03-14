<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Str;

class Session extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'last_activity'
    ];

    protected static function boot()
    {
        parent::boot();

        // Tự động set UUID khi tạo mới
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $casts = [
        'last_activity' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}