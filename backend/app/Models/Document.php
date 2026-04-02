<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'page_count',
        'extracted_text',
        'source_type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'page_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class);
    }

    public function audioSummaries(): HasMany
    {
        return $this->hasMany(AudioSummary::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function youtubeVideo(): HasMany
    {
        return $this->hasMany(YoutubeVideo::class);
    }
}
