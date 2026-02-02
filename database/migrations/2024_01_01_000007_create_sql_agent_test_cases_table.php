<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->getConnection())->create('sql_agent_test_cases', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->string('name');
            $table->text('question');
            $table->json('expected_values')->nullable();
            $table->text('golden_sql')->nullable();
            $table->json('golden_result')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sql_agent_test_cases');
    }

    public function getConnection(): ?string
    {
        return config('sql-agent.database.storage_connection');
    }
};
