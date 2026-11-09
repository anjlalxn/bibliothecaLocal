<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    protected $primaryKey = 'borrowing_id';

    public $timestamps = false;

    protected $fillable = [
        'book_id',
        'member_id',
        'user_id',
        'borrowing_date',
        'due_date',
        'return_date',
        'status',
    ];

    protected $casts = [
        'borrowing_date' => 'date',
        'due_date'       => 'date',
        'return_date'    => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id', 'book_id');
    }

    // Shown in the table: worked out from the dates, same rule the dashboard uses
    public function getDisplayStatusAttribute(): string
    {
        if ($this->return_date) {
            return 'Returned';
        }

        if ($this->due_date && $this->due_date->lt(today())) {
            return 'Overdue';
        }

        return 'Borrowed';
    }

    // Reusable search + filter rules (the CSV export uses this)
    public function scopeFilter(Builder $query, array $f): Builder
    {
        $search = trim((string) ($f['search'] ?? ''));
        $status = $f['status'] ?? null;

        return $query
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->whereHas('member', function ($m) use ($search) {
                        $m->where('full_name', 'like', "%{$search}%")
                          ->orWhere('member_code', 'like', "%{$search}%");
                    })->orWhereHas('book', function ($b) use ($search) {
                        $b->where('title', 'like', "%{$search}%");
                    });
                });
            })
            ->when($status === 'borrowed',  fn ($q) => $q->whereNull('return_date')->whereDate('due_date', '>=', today()))
            ->when($status === 'due_today', fn ($q) => $q->whereNull('return_date')->whereDate('due_date', today()))
            ->when($status === 'overdue',   fn ($q) => $q->whereNull('return_date')->whereDate('due_date', '<', today()))
            ->when($status === 'returned',  fn ($q) => $q->whereNotNull('return_date'))
            ->when($f['from'] ?? null, fn ($q, $from) => $q->whereDate('borrowing_date', '>=', $from))
            ->when($f['to'] ?? null,   fn ($q, $to)   => $q->whereDate('borrowing_date', '<=', $to));
    }
}