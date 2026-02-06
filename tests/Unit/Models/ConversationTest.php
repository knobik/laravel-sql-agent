<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Knobik\SqlAgent\Enums\MessageRole;
use Knobik\SqlAgent\Models\Conversation;
use Knobik\SqlAgent\Models\Message;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
});

describe('Conversation', function () {
    it('can be created', function () {
        $conversation = Conversation::create([
            'title' => 'Test conversation',
            'connection' => 'default',
        ]);

        expect($conversation->title)->toBe('Test conversation');
    });

    it('has many messages', function () {
        $conversation = Conversation::create(['title' => 'Test']);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User,
            'content' => 'Hello',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Assistant,
            'content' => 'Hi there',
        ]);

        expect($conversation->messages()->count())->toBe(2);
    });

    it('can generate title from first message', function () {
        $conversation = Conversation::create([]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User,
            'content' => 'How many users do we have?',
        ]);

        expect($conversation->generateTitle())->toBe('How many users do we have?');
    });
});
