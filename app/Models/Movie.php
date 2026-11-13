<?php

namespace App\Models;

use App\Support\MovieGenres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Schema;

class Movie extends Model
{
    protected $attributes = [
        'age_rating_verified' => false,
        'is_featured' => false,
    ];

    protected $fillable = [
        'title',
        'genre',
        'duration',
        'label',
        'poster',
        'description',
        'status',
        'trailer_url',
        'age_rating',
        'age_rating_verified',
        'is_featured',
        'format_id',
    ];

    protected function casts(): array
    {
        return [
            'age_rating_verified' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getGenreLabelsAttribute(): array
    {
        return MovieGenres::split($this->genre);
    }

    public function ageRatingLabel(): string
    {
        return $this->age_rating_verified ? (string) $this->age_rating : 'Chưa xác nhận';
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function formats(): BelongsToMany
    {
        return $this->belongsToMany(Format::class, 'movie_format');
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(Format::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Booking::class,
            Showtime::class,
            'movie_id',
            'showtime_id',
            'id',
            'id'
        );
    }

    public function favoritedBy(): BelongsToMany
    {
        if (! Schema::hasTable('movie_favorites')) {
            return $this->belongsToMany(User::class, 'movie_favorites')
                ->whereRaw('1 = 0')
                ->withTimestamps();
        }

        return $this->belongsToMany(User::class, 'movie_favorites')->withTimestamps();
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (! $user || ! Schema::hasTable('movie_favorites')) {
            return false;
        }

        return $this->favoritedBy()->whereKey($user->getKey())->exists();
    }
}
