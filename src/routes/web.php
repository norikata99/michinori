<?php

use App\Http\Controllers\GoalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
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
    // タスクはゴールの配下に作る。作成は /goals/{goal}/tasks、編集・削除は /tasks/{task}（shallow）
    Route::resource('goals.tasks', TaskController::class)->shallow()->except(['index', 'show']);
});

require __DIR__.'/auth.php';
