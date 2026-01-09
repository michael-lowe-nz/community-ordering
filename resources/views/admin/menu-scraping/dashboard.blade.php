<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Menu Scraping Dashboard
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to Admin Dashboard
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

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Scraping Summary -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Scraping Summary (Last 7 Days)</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600">{{ $scrapingSummary['total_scrapes'] ?? 0 }}</p>
                        <p class="text-gray-600">Total Scrapes</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-2xl font-bold text-green-600">{{ $scrapingSummary['successful_scrapes'] ?? 0 }}</p>
                        <p class="text-gray-600">Successful Scrapes</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <p class="text-2xl font-bold text-red-600">{{ $scrapingSummary['failed_scrapes'] ?? 0 }}</p>
                        <p class="text-gray-600">Failed Scrapes</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xl font-semibold">Items</p>
                        <div class="flex justify-between mt-2">
                            <div>
                                <p class="text-gray-600">Found:</p>
                                <p class="font-bold">{{ $scrapingSummary['total_items_found'] ?? 0 }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Created:</p>
                                <p class="font-bold">{{ $scrapingSummary['total_items_created'] ?? 0 }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Updated:</p>
                                <p class="font-bold">{{ $scrapingSummary['total_items_updated'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xl font-semibold">Performance</p>
                        <div class="flex justify-between mt-2">
                            <div>
                                <p class="text-gray-600">Success Rate:</p>
                                <p class="font-bold">{{ $scrapingSummary['success_rate_percent'] ?? 0 }}%</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Avg Duration:</p>
                                <p class="font-bold">{{ $scrapingSummary['avg_duration_seconds'] ?? 0 }}s</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restaurants Needing Scraping -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Restaurants Due for Scraping</h3>
                    
                    @if($restaurantsNeedingScraping->count() > 0)
                        <form method="POST" action="{{ route('admin.menu-scraping.scrape-multiple') }}">
                            @csrf
                            @foreach($restaurantsNeedingScraping as $restaurant)
                                <input type="hidden" name="restaurant_ids[]" value="{{ $restaurant->id }}">
                            @endforeach
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Scrape All Due
                            </button>
                        </form>
                    @endif
                </div>
                
                @if($restaurantsNeedingScraping->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Restaurant</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Scraped</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($restaurantsNeedingScraping as $restaurant)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $restaurant->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                {{ $restaurant->last_menu_scrape ? $restaurant->last_menu_scrape->diffForHumans() : 'Never' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ ucfirst($restaurant->menu_scrape_frequency ?? 'Not set') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" action="{{ route('admin.menu-scraping.scrape-restaurant', $restaurant->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900 mr-3">Scrape Now</button>
                                            </form>
                                            <a href="{{ route('admin.menu-scraping.history', $restaurant->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">History</a>
                                            <a href="{{ route('admin.menu-scraping.edit-menu-url', $restaurant->id) }}" class="text-gray-600 hover:text-gray-900">Edit URL</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-gray-50 p-4 rounded text-center">
                        <p class="text-gray-600">No restaurants are currently due for scraping.</p>
                    </div>
                @endif
            </div>

            <!-- All Restaurants with Scraping Enabled -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">{{ isset($showAll) && $showAll ? 'All Restaurants' : 'All Restaurants with Scraping Enabled' }}</h3>
                    <div>
                        @if(isset($showAll) && $showAll)
                            <a href="{{ route('admin.menu-scraping.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Show Only Scraping Enabled
                            </a>
                        @else
                            <a href="{{ route('admin.menu-scraping.dashboard', ['show_all' => 'true']) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Show All Restaurants
                            </a>
                        @endif
                    </div>
                </div>
                
                @if($restaurants->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Restaurant</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Menu URL</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Scraped</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($restaurants as $restaurant)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $restaurant->name }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500 truncate max-w-xs">
                                                @if($restaurant->menu_url)
                                                    <a href="{{ $restaurant->menu_url }}" target="_blank" class="text-blue-600 hover:text-blue-900">
                                                        {{ $restaurant->menu_url }}
                                                    </a>
                                                @else
                                                    <span class="text-red-500">Not set</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                {{ $restaurant->last_menu_scrape ? $restaurant->last_menu_scrape->diffForHumans() : 'Never' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ ucfirst($restaurant->menu_scrape_frequency ?? 'Not set') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" action="{{ route('admin.menu-scraping.scrape-restaurant', $restaurant->id) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="force" value="true">
                                                <button type="submit" class="text-green-600 hover:text-green-900 mr-3">Force Scrape</button>
                                            </form>
                                            <a href="{{ route('admin.menu-scraping.history', $restaurant->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">History</a>
                                            <a href="{{ route('admin.menu-scraping.edit-menu-url', $restaurant->id) }}" class="text-gray-600 hover:text-gray-900">Edit URL</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-gray-50 p-4 rounded text-center">
                        <p class="text-gray-600">No restaurants have scraping enabled.</p>
                        <p class="mt-2">
                            <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-900">
                                Go to admin dashboard to add restaurants
                            </a>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>