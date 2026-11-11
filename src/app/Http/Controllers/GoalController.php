<?php

namespace App\Http\Controllers;

use App\Enums\GoalStatus;
use App\Http\Requests\GoalRequest;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function index(Request $request): View
    {
        // 自分のゴールだけを、締切が近い順に出す
        $goals = $request->user()->goals()
            ->orderBy('planned_end')
            ->orderBy('id')
            ->get();

        return view('goals.index', ['goals' => $goals]);
    }

    public function create(): View
    {
        return view('goals.create', [
            'goal' => new Goal(['status' => GoalStatus::InProgress]),
            'statuses' => GoalStatus::cases(),
        ]);
    }

    public function store(GoalRequest $request): RedirectResponse
    {
        $request->user()->goals()->create($request->validated());

        return redirect()->route('goals.index')->with('status', 'ゴールを作成しました。');
    }

    public function edit(Goal $goal): View
    {
        Gate::authorize('update', $goal);

        return view('goals.edit', [
            'goal' => $goal,
            'statuses' => GoalStatus::cases(),
        ]);
    }

    public function update(GoalRequest $request, Goal $goal): RedirectResponse
    {
        Gate::authorize('update', $goal);

        $goal->update($request->validated());

        return redirect()->route('goals.index')->with('status', 'ゴールを更新しました。');
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        Gate::authorize('delete', $goal);

        $goal->delete();

        return redirect()->route('goals.index')->with('status', 'ゴールを削除しました。');
    }
}
