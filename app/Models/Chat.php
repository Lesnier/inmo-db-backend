<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'inmo_chats';

    protected $fillable = [
        'uuid',
        'type',
        'subject',
        'contact_id',
        'last_message_at', // Optional caching
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'inmo_chat_participants', 'chat_id', 'user_id')
                    ->withPivot('last_read_at', 'is_muted')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_id');
    }
    
    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'chat_id')->latestOfMany();
    }
}
