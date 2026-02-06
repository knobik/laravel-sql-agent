<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Knobik\SqlAgent\Enums\MessageRole;
use Knobik\SqlAgent\Models\Conversation;
use Knobik\SqlAgent\Models\Message;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
});

describe('Message', function () {
    it('can be created', function () {
        $conversation = Conversation::create([]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User,
            'content' => 'Test message',
        ]);

        expect($message->role)->toBe(MessageRole::User);
        expect($message->isFromUser())->toBeTrue();
    });

    it('can have sql and results', function () {
        $conversation = Conversation::create([]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Assistant,
            'content' => 'Here are the results',
            'sql' => 'SELECT * FROM users',
            'results' => [['id' => 1, 'name' => 'John']],
        ]);

        expect($message->hasSql())->toBeTrue();
        expect($message->hasResults())->toBeTrue();
        expect($message->getResultCount())->toBe(1);
    });

    it('scopes by role', function () {
        $conversation = Conversation::create([]);
        Message::create(['conversation_id' => $conversation->id, 'role' => MessageRole::User, 'content' => 'Q']);
        Message::create(['conversation_id' => $conversation->id, 'role' => MessageRole::Assistant, 'content' => 'A']);

        expect(Message::fromUser()->count())->toBe(1);
        expect(Message::fromAssistant()->count())->toBe(1);
    });
});
