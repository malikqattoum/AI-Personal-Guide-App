<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'count',
        'period_month',
        'period_year',
    ];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'period_month' => 'integer',
            'period_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getMonthlyUsage(int $userId, string $actionType, ?int $month = null, ?int $year = null): int
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        return static::where('user_id', $userId)
            ->where('action_type', $actionType)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->sum('count');
    }
}
