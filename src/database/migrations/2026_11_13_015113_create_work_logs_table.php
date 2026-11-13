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
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            // タスクを削除したら、そのタスクの作業ログも消す
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            // 作業した日。タスクの実績開始日は、この最小値を集計して出す（docs/design.md 3章）
            $table->date('worked_on');
            // 作業時間（分）。タスクの実績工数は、この合計を集計して出す
            $table->unsignedInteger('minutes');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'worked_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
