<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkLogRequest;
use App\Models\Task;
use App\Models\WorkLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// 作業ログはタスクの配下に作る。一覧と追加フォームはタスクの詳細画面に出す
class WorkLogController extends Controller
{
    public function store(WorkLogRequest $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $task->workLogs()->create($request->validated());

        return redirect()->route('tasks.show', $task)->with('status', '作業ログを追加しました。');
    }

    public function edit(WorkLog $workLog): View
    {
        Gate::authorize('update', $workLog);

        return view('work-logs.edit', [
            'task' => $workLog->task,
            'workLog' => $workLog,
        ]);
    }

    public function update(WorkLogRequest $request, WorkLog $workLog): RedirectResponse
    {
        Gate::authorize('update', $workLog);

        $workLog->update($request->validated());

        return redirect()->route('tasks.show', $workLog->task)->with('status', '作業ログを更新しました。');
    }

    public function destroy(WorkLog $workLog): RedirectResponse
    {
        Gate::authorize('delete', $workLog);

        $task = $workLog->task;
        $workLog->delete();

        return redirect()->route('tasks.show', $task)->with('status', '作業ログを削除しました。');
    }
}
