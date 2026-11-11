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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            // アカウントを削除したら、そのユーザーのゴールも消す
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('planned_start');
            $table->date('planned_end');
            // 達成した日。配下のタスクから自動で計算せず、記録として持つ（docs/design.md 3章）
            $table->date('achieved_at')->nullable();
            // 値は App\Enums\GoalStatus。値を足しやすいよう、MySQL の ENUM 型ではなく文字列で持つ
            $table->string('status', 20)->default('in_progress');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
