<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Ordre manuel parmi les éléments frères (même parent), via la colonne `position`.
 * Le modèle déclare la clé du parent dans positionParentKey().
 */
trait HasPosition
{
    abstract protected function positionParentKey(): string;

    /** Éléments frères, dans l'ordre affiché. */
    public function siblings()
    {
        $key = $this->positionParentKey();

        return static::query()->where($key, $this->{$key})->orderBy('position')->orderBy('id');
    }

    public static function nextPosition(int $parentId, string $parentKey): int
    {
        return ((int) static::query()->where($parentKey, $parentId)->max('position')) + 1;
    }

    /** Monte (-1) ou descend (+1) l'élément d'un cran, puis renumérote 1, 2, 3… */
    public function move(int $offset): void
    {
        DB::transaction(function () use ($offset) {
            $ids = $this->siblings()->pluck('id')->all();
            $from = array_search($this->id, $ids, true);
            $to = $from + $offset;
            if ($from === false || $to < 0 || $to >= count($ids)) {
                return;
            }
            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];
            foreach ($ids as $i => $id) {
                static::query()->whereKey($id)->update(['position' => $i + 1]);
            }
        });
    }
}
