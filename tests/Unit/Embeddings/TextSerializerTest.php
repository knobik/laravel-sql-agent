<?php

use Knobik\SqlAgent\Contracts\Searchable;
use Knobik\SqlAgent\Embeddings\TextSerializer;

test('serialize converts searchable array to labeled text', function () {
    $model = new class implements Searchable
    {
        public function getSearchableColumns(): array
        {
            return ['title', 'description'];
        }

        public function toSearchableArray(): array
        {
            return [
                'title' => 'Test Title',
                'description' => 'Test description text',
            ];
        }
    };

    $serializer = new TextSerializer;
    $result = $serializer->serialize($model);

    expect($result)->toBe("title: Test Title\ndescription: Test description text");
});

test('serialize skips null values', function () {
    $model = new class implements Searchable
    {
        public function getSearchableColumns(): array
        {
            return ['title', 'description'];
        }

        public function toSearchableArray(): array
        {
            return [
                'title' => 'Test Title',
                'description' => null,
            ];
        }
    };

    $serializer = new TextSerializer;
    $result = $serializer->serialize($model);

    expect($result)->toBe('title: Test Title');
});

test('serialize skips empty string values', function () {
    $model = new class implements Searchable
    {
        public function getSearchableColumns(): array
        {
            return ['title'];
        }

        public function toSearchableArray(): array
        {
            return [
                'title' => '',
                'name' => 'Something',
            ];
        }
    };

    $serializer = new TextSerializer;
    $result = $serializer->serialize($model);

    expect($result)->toBe('name: Something');
});

test('serialize handles array values by imploding', function () {
    $model = new class implements Searchable
    {
        public function getSearchableColumns(): array
        {
            return ['tags'];
        }

        public function toSearchableArray(): array
        {
            return [
                'tags' => ['php', 'laravel', 'sql'],
            ];
        }
    };

    $serializer = new TextSerializer;
    $result = $serializer->serialize($model);

    expect($result)->toBe('tags: php, laravel, sql');
});

test('serialize returns empty string for empty searchable array', function () {
    $model = new class implements Searchable
    {
        public function getSearchableColumns(): array
        {
            return [];
        }

        public function toSearchableArray(): array
        {
            return [];
        }
    };

    $serializer = new TextSerializer;
    $result = $serializer->serialize($model);

    expect($result)->toBe('');
});
