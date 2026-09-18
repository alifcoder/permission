<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 */

namespace Alif\Permissions\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves mixed user input (models, primary keys, names) into primary keys.
 *
 * The model using this concern must also use the "HasUuidPrimaryKey" concern.
 */
trait ResolvesKeys
{
    /**
     * Columns used to look up a model by a human readable value.
     * The value tells whether the column is stored upper-cased.
     *
     * @return array<string, bool>
     */
    abstract protected static function searchableColumns(): array;

    /**
     * Resolve the given value into a flat, unique array of primary keys.
     * All unknown labels are resolved within a single query.
     *
     * @return array<int, int|string>
     */
    public static function resolveKeys(mixed $value): array
    {
        $keys   = [];
        $labels = [];

        foreach (is_iterable($value) ? $value : [$value] as $item) {
            if ($item instanceof Model) {
                if ($item->getKey() !== null) {
                    $keys[] = $item->getKey();
                }
            } elseif (static::looksLikeKey($item)) {
                // numeric strings are cast to int so that "1" and 1 are one key
                $keys[] = is_string($item) && static::usesUuid() === false ? (int)$item : $item;
            } elseif (is_string($item) && trim($item) !== '') {
                $labels[] = trim($item);
            }
        }

        if ($labels !== []) {
            $keys = array_merge($keys, static::query()
                    ->where(function (Builder $builder) use ($labels) {
                        foreach (static::searchableColumns() as $column => $upperCased) {
                            $builder->orWhereIn($column, $upperCased
                                    ? array_map(mb_strtoupper(...), $labels)
                                    : $labels);
                        }
                    })
                    ->pluck((new static)->getKeyName())
                    ->all());
        }

        return array_values(array_unique($keys, SORT_REGULAR));
    }

    /**
     * Check whether the given value is a primary key rather than a label.
     */
    protected static function looksLikeKey(mixed $item): bool
    {
        if (is_int($item)) {
            return true;
        }

        if (is_string($item) === false) {
            return false;
        }

        return static::usesUuid() ? checkToUUID($item) : ctype_digit($item);
    }
}
