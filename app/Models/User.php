<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Duplicate import removed

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string"),
 *     @OA\Property(property="role_id", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    // CRM Relationships
    
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'owner_id'); // Contacts owned by this user
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'owner_id');
    }

    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'inmo_chat_participants', 'user_id', 'chat_id')
                    ->withPivot(['last_read_at', 'is_muted'])
                    ->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'owner_id');
    }

    public function tasksAsAssignee(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }
    
    public function tasksCreated(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function meetingsHosted(): HasMany
    {
        return $this->hasMany(Meeting::class, 'host_id');
    }

    public function properties(): HasMany
    {
        // Get all properties published by this user, regardless of the role (publisher_type)
        return $this->hasMany(Property::class, 'publisher_id');
    }

    public function favorite_properties(): BelongsToMany
    {
        return $this->belongsToMany(
            Property::class,
            'inmo_favorites', // Updated table name if changed, or keep 'property_favorites' if that was preserved
            'user_id',
            'property_id'
        );
    }

    public function agent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Agent::class, 'user_id');
    }
}
