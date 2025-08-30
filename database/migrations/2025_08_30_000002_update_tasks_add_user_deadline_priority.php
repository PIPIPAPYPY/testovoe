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
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('tasks', 'deadline')) {
                $table->dropColumn('deadline');
            }
            if (Schema::hasColumn('tasks', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};

