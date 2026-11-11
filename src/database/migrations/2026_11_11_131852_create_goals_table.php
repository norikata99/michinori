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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            // アカウントを削除したら、そのユーザーのゴールも消す
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('planned_start');
            $table->date('planned_end');
            // 達成した日。配下のタスクから自動で計算せず、記録として持つ（docs/design.md 3章）
            $table->date('achieved_at')->nullable();
            // 値は App\Enums\GoalStatus。MySQL の ENUM 型ではなく文字列 + CHECK 制約で持つ（docs/adr/0004）
            $table->string('status', 20)->default('in_progress');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 想定外の値が入ると、enum への変換で画面が 500 になるため、DB 側でも値を制限する。
        // Laravel のスキーマビルダーは CHECK 制約を作れないので SQL で足す。
        // SQLite（テスト用）は ALTER TABLE で制約を足せないため、MySQL のときだけ付ける。
        // 値は GoalStatus から組み立てず、ここに書く（enum を変えたときに、過去のマイグレーションの意味が変わらないように）。
        // 値を足すときは、この制約を DROP CHECK して作り直すマイグレーションを書く
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE goals ADD CONSTRAINT goals_status_check CHECK (status IN ('in_progress', 'achieved', 'cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
