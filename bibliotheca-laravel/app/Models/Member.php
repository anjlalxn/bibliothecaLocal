<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $primaryKey = 'member_id';

    public $timestamps = false;

    protected $fillable = [
        'member_code',
        'full_name',
        'email',
        'status',
    ];

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'member_id', 'member_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}