<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (self $notification) {
            \App\Events\NotificationsChanged::forRecipients(collect([$notification]));
        });
    }

    public function newEloquentBuilder($query)
    {
        return new \App\Models\Builders\NotificationBuilder($query);
    }

    protected $fillable = [
        'empid',
        'lapp_id',
        'esign_id',
        'category',
        'utype',
        'module',
        'status',
    ];

}
