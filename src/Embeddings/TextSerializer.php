<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Embeddings;

use Knobik\SqlAgent\Contracts\Searchable;

class TextSerializer
{
    /**
     * Serialize a searchable model into labeled text suitable for embedding.
     */
    public function serialize(Searchable $model): string
    {
        $data = $model->toSearchableArray();

        $lines = [];
        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $lines[] = $key.': '.(is_array($value) ? implode(', ', $value) : (string) $value);
        }

        return implode("\n", $lines);
    }
}
