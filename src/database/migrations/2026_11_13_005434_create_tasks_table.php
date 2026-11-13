<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            // ゴールを削除したら、配下のタスクも消す
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('planned_start');
            $table->date('planned_end');
            // 見積もり工数（分）。実績工数は作業ログから集計し、ここには持たない（docs/design.md 3章）
            $table->unsignedInteger('estimated_minutes');
            // 値は App\Enums\TaskStatus。文字列 + CHECK 制約で持つ（docs/adr/0004）
            $table->string('status', 20)->default('todo');
            // 完了した日。状態を「完了」にすると記録される
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->index(['goal_id', 'planned_end']);
        });

        // SQLite（テスト用）は ALTER TABLE で制約を足せないため、MySQL のときだけ付ける。
        // 値は TaskStatus から組み立てず、ここに書く（docs/adr/0004）
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_check CHECK (status IN ('todo', 'doing', 'done'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
