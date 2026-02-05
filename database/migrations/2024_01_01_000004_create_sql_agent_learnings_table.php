<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->getConnection())->create('sql_agent_learnings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable();
            $table->text('sql')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('category');
        });

        // Add full-text index for MySQL
        if (Schema::connection($this->getConnection())->getConnection()->getDriverName() === 'mysql') {
            Schema::connection($this->getConnection())->table('sql_agent_learnings', function (Blueprint $table) {
                $table->fullText(['title', 'description']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sql_agent_learnings');
    }

    public function getConnection(): ?string
    {
        return config('sql-agent.database.storage_connection');
    }
};
