<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->getConnection())->create('sql_agent_query_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('question');
            $table->text('sql');
            $table->text('summary')->nullable();
            $table->json('tables_used')->nullable();
            $table->text('data_quality_notes')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        // Add full-text index for MySQL
        if (Schema::connection($this->getConnection())->getConnection()->getDriverName() === 'mysql') {
            Schema::connection($this->getConnection())->table('sql_agent_query_patterns', function (Blueprint $table) {
                $table->fullText(['name', 'question', 'summary']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sql_agent_query_patterns');
    }

    public function getConnection(): ?string
    {
        return config('sql-agent.database.storage_connection');
    }
};
