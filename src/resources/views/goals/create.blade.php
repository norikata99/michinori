<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">ゴールを作成</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('goals.store') }}" class="space-y-6">
                    @csrf

                    @include('goals._form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>作成</x-primary-button>
                        <a href="{{ route('goals.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">戻る</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
