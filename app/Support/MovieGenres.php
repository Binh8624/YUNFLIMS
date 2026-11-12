<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class MovieGenres
{
    public static function split(?string $genres): array
    {
        if ($genres === null || trim($genres) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('trim', explode(',', $genres)),
            fn (string $genre) => $genre !== ''
        )));
    }

    public static function options(iterable $genres): array
    {
        $options = [];

        foreach ($genres as $genre) {
            foreach (self::split($genre) as $label) {
                $options[$label] = $label;
            }
        }

        $options = array_values($options);
        sort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    public static function applyFilter(Builder $query, string $genre, string $column = 'genre'): Builder
    {
        return $query->where(function (Builder $query) use ($genre, $column) {
            $normalizedColumn = "REPLACE(REPLACE({$column}, ', ', ','), ' ,', ',')";

            $query->whereRaw("{$normalizedColumn} = ?", [$genre])
                ->orWhereRaw("{$normalizedColumn} LIKE ?", [$genre.',%'])
                ->orWhereRaw("{$normalizedColumn} LIKE ?", ['%,'.$genre.',%'])
                ->orWhereRaw("{$normalizedColumn} LIKE ?", ['%,'.$genre]);
        });
    }
}
