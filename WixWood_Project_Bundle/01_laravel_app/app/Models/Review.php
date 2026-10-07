<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasUuids;

    protected $fillable = ['rating', 'name', 'piece', 'comment', 'status'];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
