<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <h1 class="text-xl font-bold">@yield('title', '管理画面')</h1>
            </div>
            <div class="flex items-center">
                <form action="{{ route('admin.logout') }}" method="POST" class="ml-4">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-gray-900">ログアウト</button>
                </form>
            </div>
        </div>
    </div>
</nav>


