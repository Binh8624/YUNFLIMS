<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Movie extends Model
{
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
    ];

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function formats(): BelongsToMany
    {
        return $this->belongsToMany(Format::class, 'movie_format');
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
}