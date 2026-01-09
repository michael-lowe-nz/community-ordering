<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Menu URL: {{ $restaurant->name }}
            </h2>
            <a href="{{ route('admin.menu-scraping.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to Scraping Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Menu Scraping Configuration</h3>

                @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.menu-scraping.update-menu-url', $restaurant->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-6">
                        <label for="menu_url" class="block text-sm font-medium text-gray-700 mb-1">Menu URL</label>
                        <input
                            type="url"
                            name="menu_url"
                            id="menu_url"
                            value="{{ old('menu_url', $restaurant->menu_url) }}"
                            class="w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                            placeholder="https://example.com/menu">
                        <p class="mt-1 text-sm text-gray-500">Enter the full URL to the restaurant's menu page.</p>
                    </div>

                    <div class="mb-6">
                        <label for="menu_scraping_enabled" class="flex items-center">
                            <input
                                type="checkbox"
                                name="menu_scraping_enabled"
                                id="menu_scraping_enabled"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                {{ old('menu_scraping_enabled', $restaurant->menu_scraping_enabled) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">Enable menu scraping for this restaurant</span>
                        </label>
                    </div>

                    <div class="mb-6">
                        <label for="menu_scrape_frequency" class="block text-sm font-medium text-gray-700 mb-1">Scraping Frequency</label>
                        <select
                            name="menu_scrape_frequency"
                            id="menu_scrape_frequency"
                            class="w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm">
                            <option value="">Select frequency</option>
                            <option value="daily" {{ old('menu_scrape_frequency', $restaurant->menu_scrape_frequency) === 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ old('menu_scrape_frequency', $restaurant->menu_scrape_frequency) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ old('menu_scrape_frequency', $restaurant->menu_scrape_frequency) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="scraping_notes" class="block text-sm font-medium text-gray-700 mb-1">Scraping Notes</label>
                        <textarea
                            name="scraping_notes"
                            id="scraping_notes"
                            rows="3"
                            class="w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                            placeholder="Add any notes about scraping this restaurant's menu">{{ old('scraping_notes', $restaurant->scraping_notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('admin.menu-scraping.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>