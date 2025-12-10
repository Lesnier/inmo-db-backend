<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Task extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_tasks';
    
    /**
     * @OA\Schema(
     *     schema="Task",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="due_date", type="string", format="date-time"),
     *     @OA\Property(property="status", type="string"),
     *     @OA\Property(property="priority", type="string"),
     *     @OA\Property(property="assigned_to", type="integer"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
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
