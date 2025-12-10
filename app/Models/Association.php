<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Association extends Model
{
    use HasFactory;

    protected $table = 'inmo_associations';

    protected $fillable = [
        'object_type_a',
        'object_id_a',
        'object_type_b',
        'object_id_b',
        'type',
    ];

    public function objectA()
    {
        return $this->morphTo(null, 'object_type_a', 'object_id_a');
    }

    public function objectB()
    {
        return $this->morphTo(null, 'object_type_b', 'object_id_b');
    }
}
