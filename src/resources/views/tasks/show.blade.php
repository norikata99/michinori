<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm text-gray-600">
                    <a href="{{ route('goals.show', $goal) }}" class="underline hover:text-gray-900">{{ $goal->title }}</a>
                </p>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">{{ $task->title }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $task->planned_start->toDateString() }} 〜 {{ $task->planned_end->toDateString() }}
                    ・見積もり <x-minutes :value="$task->estimated_minutes" />
                    ・{{ $task->status->label() }}
                    @if ($task->completed_at)
                        ・完了日 {{ $task->completed_at->toDateString() }}
                    @endif
                </p>
            </div>
            <a href="{{ route('tasks.edit', $task) }}" class="shrink-0 text-sm text-indigo-600 hover:text-indigo-900">タスクを編集</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 text-sm text-green-800 bg-green-50 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-semibold text-lg text-gray-800">作業ログを追加</h3>
                <form method="post" action="{{ route('tasks.work-logs.store', $task) }}" class="mt-4 space-y-6">
                    @csrf

                    @include('work-logs._form', ['workLog' => $newWorkLog])

                    <x-primary-button>追加</x-primary-button>
                </form>
            </div>

            <div>
                <div class="flex items-baseline justify-between">
                    <h3 class="font-semibold text-lg text-gray-800">作業ログ</h3>
                    <p class="text-sm text-gray-700">合計 <x-minutes :value="$totalMinutes" class="font-semibold" />（見積もり <x-minutes :value="$task->estimated_minutes" />）</p>
                </div>

                <div class="mt-4 bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                    @if ($workLogs->isEmpty())
                        <p class="p-6 text-gray-600">まだ作業ログがありません。</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th class="px-6 py-3 font-medium">作業日</th>
                                    <th class="px-6 py-3 font-medium">作業時間</th>
                                    <th class="px-6 py-3 font-medium">メモ</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-gray-900">
                                @foreach ($workLogs as $workLog)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $workLog->worked_on->toDateString() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap"><x-minutes :value="$workLog->minutes" /></td>
                                        <td class="px-6 py-4 whitespace-pre-line">{{ $workLog->note }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <a href="{{ route('work-logs.edit', $workLog) }}" class="text-indigo-600 hover:text-indigo-900">編集</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
