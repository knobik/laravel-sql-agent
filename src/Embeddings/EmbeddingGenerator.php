<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Embeddings;

use Prism\Prism\Facades\Prism;

class EmbeddingGenerator
{
    /**
     * Generate an embedding vector for a single text input.
     *
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $response = Prism::embeddings()
            ->using($this->provider(), $this->model())
            ->fromInput($text)
            ->asEmbeddings();

        return $response->embeddings[0]->embedding;
    }

    /**
     * Generate embedding vectors for multiple text inputs.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedBatch(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $response = Prism::embeddings()
            ->using($this->provider(), $this->model())
            ->fromArray($texts)
            ->asEmbeddings();

        return array_map(
            fn ($embedding) => $embedding->embedding,
            $response->embeddings
        );
    }

    protected function provider(): string
    {
        return config('sql-agent.embeddings.provider', 'openai');
    }

    protected function model(): string
    {
        return config('sql-agent.embeddings.model', 'text-embedding-3-small');
    }
}
