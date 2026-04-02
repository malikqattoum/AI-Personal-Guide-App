<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flashcard extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'document_id',
        'user_id',
        'front_text',
        'back_text',
        'difficulty',
        'times_reviewed',
        'times_correct',
        'last_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'times_reviewed' => 'integer',
            'times_correct' => 'integer',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
