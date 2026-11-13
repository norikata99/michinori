<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $goal->title }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $goal->planned_start->toDateString() }} 〜 {{ $goal->planned_end->toDateString() }}（締切）
                    ・{{ $goal->status->label() }}
                    @if ($goal->achieved_at)
                        ・達成日 {{ $goal->achieved_at->toDateString() }}
                    @endif
                </p>
            </div>
            <a href="{{ route('goals.edit', $goal) }}" class="shrink-0 text-sm text-indigo-600 hover:text-indigo-900">ゴールを編集</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 text-sm text-green-800 bg-green-50 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-lg text-gray-800">タスク</h3>
                <a href="{{ route('goals.tasks.create', $goal) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">新しいタスク</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                @if ($tasks->isEmpty())
                    <p class="p-6 text-gray-600">まだタスクがありません。</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th class="px-6 py-3 font-medium">タイトル</th>
                                <th class="px-6 py-3 font-medium">開始予定日</th>
                                <th class="px-6 py-3 font-medium">終了予定日</th>
                                <th class="px-6 py-3 font-medium">見積もり</th>
                                <th class="px-6 py-3 font-medium">状態</th>
                                <th class="px-6 py-3 font-medium">完了日</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-900">
                            @foreach ($tasks as $task)
                                <tr>
                                    <td class="px-6 py-4"><a href="{{ route('tasks.show', $task) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">{{ $task->title }}</a></td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $task->planned_start->toDateString() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $task->planned_end->toDateString() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><x-minutes :value="$task->estimated_minutes" /></td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $task->status->label() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $task->completed_at?->toDateString() ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <a href="{{ route('tasks.edit', $task) }}" class="text-indigo-600 hover:text-indigo-900">編集</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <a href="{{ route('goals.index') }}" class="inline-block text-sm text-gray-600 underline hover:text-gray-900">ゴールの一覧へ</a>
        </div>
    </div>
</x-app-layout>
