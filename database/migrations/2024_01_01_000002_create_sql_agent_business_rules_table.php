<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->getConnection())->create('sql_agent_business_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['metric', 'rule', 'gotcha']);
            $table->string('name');
            $table->text('description');
            $table->json('conditions')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('sql_agent_business_rules');
    }

    public function getConnection(): ?string
    {
        return config('sql-agent.database.storage_connection');
    }
};
