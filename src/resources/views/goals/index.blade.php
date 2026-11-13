<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">ゴール</h2>
            <a href="{{ route('goals.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">新しいゴール</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 text-sm text-green-800 bg-green-50 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                @if ($goals->isEmpty())
                    <p class="p-6 text-gray-600">まだゴールがありません。</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th class="px-6 py-3 font-medium">タイトル</th>
                                <th class="px-6 py-3 font-medium">開始予定日</th>
                                <th class="px-6 py-3 font-medium">締切</th>
                                <th class="px-6 py-3 font-medium">状態</th>
                                <th class="px-6 py-3 font-medium">達成日</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-900">
                            @foreach ($goals as $goal)
                                <tr>
                                    <td class="px-6 py-4"><a href="{{ route('goals.show', $goal) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">{{ $goal->title }}</a></td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $goal->planned_start->toDateString() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $goal->planned_end->toDateString() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $goal->status->label() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $goal->achieved_at?->toDateString() ?? '—' }}</td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <a href="{{ route('goals.edit', $goal) }}" class="text-indigo-600 hover:text-indigo-900">編集</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
