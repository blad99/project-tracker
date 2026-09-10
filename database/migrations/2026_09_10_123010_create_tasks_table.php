<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_task_id')->nullable();
            $table->string('name');
            $table->enum('status', ['Draft', 'In Progress', 'Done'])->default('Draft');
            $table->unsignedInteger('weight')->default(1);
            $table->timestamps();
            $table->foreign('parent_task_id')->references('id')->on('tasks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
