<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->getConnection())->create('sql_agent_table_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('connection')->default('default');
            $table->string('table_name');
            $table->text('description')->nullable();
            $table->json('columns')->nullable();
            $table->json('relationships')->nullable();
            $table->json('data_quality_notes')->nullable();
            $table->timestamps();

            $table->unique(['connection', 'table_name']);
            $table->index('table_name');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sql_agent_table_metadata');
    }

    public function getConnection(): ?string
    {
        return config('sql-agent.database.storage_connection');
    }
};
