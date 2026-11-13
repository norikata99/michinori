<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">作業ログを編集</h2>
        <p class="mt-1 text-sm text-gray-600">タスク：{{ $task->title }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('work-logs.update', $workLog) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    @include('work-logs._form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>更新</x-primary-button>
                        <a href="{{ route('tasks.show', $task) }}" class="text-sm text-gray-600 underline hover:text-gray-900">戻る</a>
                    </div>
                </form>
            </div>

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('work-logs.destroy', $workLog) }}" onsubmit="return confirm('この作業ログを削除します。元に戻せません。よろしいですか？')">
                    @csrf
                    @method('delete')

                    <x-danger-button>この作業ログを削除</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
