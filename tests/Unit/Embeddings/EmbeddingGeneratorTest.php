<?php

use Knobik\SqlAgent\Embeddings\EmbeddingGenerator;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\EmbeddingsResponseFake;
use Prism\Prism\ValueObjects\Embedding;

beforeEach(function () {
    config([
        'sql-agent.embeddings.provider' => 'openai',
        'sql-agent.embeddings.model' => 'text-embedding-3-small',
    ]);

    $this->generator = new EmbeddingGenerator;
});

test('embed generates a vector for a single text', function () {
    $fakeVector = array_fill(0, 3, 0.5);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray($fakeVector),
        ]),
    ]);

    $result = $this->generator->embed('test text');

    expect($result)->toBe($fakeVector);
});

test('embedBatch generates vectors for multiple texts', function () {
    $vector1 = [0.1, 0.2, 0.3];
    $vector2 = [0.4, 0.5, 0.6];

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray($vector1),
            Embedding::fromArray($vector2),
        ]),
    ]);

    $results = $this->generator->embedBatch(['text one', 'text two']);

    expect($results)->toHaveCount(2);
    expect($results[0])->toBe($vector1);
    expect($results[1])->toBe($vector2);
});

test('embedBatch returns empty array for empty input', function () {
    $results = $this->generator->embedBatch([]);

    expect($results)->toBe([]);
});

test('embed reads provider from config', function () {
    config(['sql-agent.embeddings.provider' => 'ollama']);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray([0.1]),
        ]),
    ]);

    $generator = new EmbeddingGenerator;
    $result = $generator->embed('test');

    expect($result)->toBe([0.1]);
});
