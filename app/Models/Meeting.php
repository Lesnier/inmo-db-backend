<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Meeting extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_meetings';

    protected $fillable = [
        'subject',
        'description',
        'meeting_type',
        'scheduled_at',
        'duration_minutes',
        'created_by',
        'host_id',
        'location',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }
}
