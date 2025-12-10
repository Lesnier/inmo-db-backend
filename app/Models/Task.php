<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Task extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_tasks';

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'due_date' => 'datetime',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
