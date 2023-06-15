<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['title', 'description', 'author', 'attachments'];

    protected function attachments(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value),
        );
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor');
    }
}
