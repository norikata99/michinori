<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\TaskRequest;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// タスクはゴールの配下に作る。一覧はゴールの詳細画面に出す
class TaskController extends Controller
{
    public function create(Goal $goal): View
    {
        Gate::authorize('update', $goal);

        return view('tasks.create', [
            'goal' => $goal,
            'task' => new Task(['status' => TaskStatus::Todo]),
            'statuses' => TaskStatus::cases(),
        ]);
    }

    public function store(TaskRequest $request, Goal $goal): RedirectResponse
    {
        Gate::authorize('update', $goal);

        $goal->tasks()->create($request->validated());

        return redirect()->route('goals.show', $goal)->with('status', 'タスクを作成しました。');
    }

    public function edit(Task $task): View
    {
        Gate::authorize('update', $task);

        return view('tasks.edit', [
            'goal' => $task->goal,
            'task' => $task,
            'statuses' => TaskStatus::cases(),
        ]);
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $task->update($request->validated());

        return redirect()->route('goals.show', $task->goal)->with('status', 'タスクを更新しました。');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $goal = $task->goal;
        $task->delete();

        return redirect()->route('goals.show', $goal)->with('status', 'タスクを削除しました。');
    }
}
