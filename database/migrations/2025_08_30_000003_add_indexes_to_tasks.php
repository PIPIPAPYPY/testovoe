<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('tasks', 'deadline')) {
                $table->dateTime('deadline')->nullable();
            }
            if (!Schema::hasColumn('tasks', 'priority')) {
                $table->unsignedTinyInteger('priority')->default(3);
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('user_id', 'idx_tasks_user_id');
            $table->index('status', 'idx_tasks_status');
            $table->index('created_at', 'idx_tasks_created_at');
            $table->index('deadline', 'idx_tasks_deadline');
            $table->index('priority', 'idx_tasks_priority');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_user_id');
            $table->dropIndex('idx_tasks_status');
            $table->dropIndex('idx_tasks_created_at');
            $table->dropIndex('idx_tasks_deadline');
            $table->dropIndex('idx_tasks_priority');
        });
    }
};

