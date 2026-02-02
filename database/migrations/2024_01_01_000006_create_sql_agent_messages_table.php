<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->getConnection())->create('sql_agent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('sql_agent_conversations')
                ->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system', 'tool']);
            $table->text('content');
            $table->text('sql')->nullable();
            $table->json('results')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sql_agent_messages');
    }

    public function getConnection(): ?string
    {
        return config('sql-agent.database.storage_connection');
    }
};
