<?php

use App\Http\Controllers\GoalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('goals', GoalController::class);
    // タスクはゴールの配下に作る。作成は /goals/{goal}/tasks、詳細・編集・削除は /tasks/{task}（shallow）
    Route::resource('goals.tasks', TaskController::class)->shallow()->except('index');
    // 作業ログはタスクの配下に作る。一覧と追加フォームはタスクの詳細画面（/tasks/{task}）に出す
    Route::resource('tasks.work-logs', WorkLogController::class)->shallow()->only(['store', 'edit', 'update', 'destroy']);
});

require __DIR__.'/auth.php';
