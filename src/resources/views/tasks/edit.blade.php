<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">タスクを編集</h2>
        <p class="mt-1 text-sm text-gray-600">ゴール：{{ $goal->title }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('tasks.update', $task) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    @include('tasks._form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>更新</x-primary-button>
                        <a href="{{ route('goals.show', $goal) }}" class="text-sm text-gray-600 underline hover:text-gray-900">戻る</a>
                    </div>
                </form>
            </div>

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('このタスクを削除します。元に戻せません。よろしいですか？')">
                    @csrf
                    @method('delete')

                    <x-danger-button>このタスクを削除</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
