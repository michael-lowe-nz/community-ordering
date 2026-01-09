<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Scraping History: {{ $restaurant->name }}
            </h2>
            <a href="{{ route('admin.menu-scraping.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to Scraping Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Restaurant Info -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Restaurant Information</h3>
                        <div class="space-y-2">
                            <p><span class="font-medium">Name:</span> {{ $restaurant->name }}</p>
                            <p><span class="font-medium">Address:</span> {{ $restaurant->address }}</p>
                            <p>
                                <span class="font-medium">Menu URL:</span> 
                                @if($restaurant->menu_url)
                                    <a href="{{ $restaurant->menu_url }}" target="_blank" class="text-blue-600 hover:text-blue-900">
                                        {{ $restaurant->menu_url }}
                                    </a>
                                @else
                                    <span class="text-red-500">Not set</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Scraping Configuration</h3>
                        <div class="space-y-2">
                            <p>
                                <span class="font-medium">Scraping Enabled:</span> 
                                @if($restaurant->menu_scraping_enabled)
                                    <span class="text-green-600">Yes</span>
                                @else
                                    <span class="text-red-600">No</span>
                                @endif
                            </p>
                            <p><span class="font-medium">Scraping Frequency:</span> {{ ucfirst($restaurant->menu_scrape_frequency ?? 'Not set') }}</p>
                            <p><span class="font-medium">Last Scraped:</span> {{ $restaurant->last_menu_scrape ? $restaurant->last_menu_scrape->format('Y-m-d H:i:s') : 'Never' }}</p>
                            <p><span class="font-medium">Notes:</span> {{ $restaurant->scraping_notes ?? 'None' }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex space-x-4">
                    <form method="POST" action="{{ route('admin.menu-scraping.scrape-restaurant', $restaurant->id) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Scrape Now
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.menu-scraping.scrape-restaurant', $restaurant->id) }}">
                        @csrf
                        <input type="hidden" name="force" value="true">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Force Scrape
                        </button>
                    </form>
                    <a href="{{ route('admin.menu-scraping.edit-menu-url', $restaurant->id) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Edit Configuration
                    </a>
                </div>
            </div>

            <!-- Scraping History -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Scraping History</h3>
                
                @if($history->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items Found</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items Created</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items Updated</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($history as $log)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $log->scraped_at->format('Y-m-d H:i:s') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ ucfirst($log->scraping_type) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $log->status === 'success' ? 'bg-green-100 text-green-800' : 
                                                   ($log->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $log->items_found ?? 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $log->items_created ?? 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $log->items_updated ?? 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $log->duration_seconds ? $log->duration_seconds . 's' : 'N/A' }}
                                        </td>
                                    </tr>
                                    @if($log->error_message)
                                        <tr class="bg-red-50">
                                            <td colspan="7" class="px-6 py-4">
                                                <div class="text-sm text-red-700">
                                                    <span class="font-medium">Error:</span> {{ $log->error_message }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-gray-50 p-4 rounded text-center">
                        <p class="text-gray-600">No scraping history available for this restaurant.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>