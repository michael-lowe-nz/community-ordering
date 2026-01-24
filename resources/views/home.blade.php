<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Enough Food - Restaurant Order Record Keeping</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <x-banner />

        <div class="min-h-screen bg-gray-50">
            <x-navigation />
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    @foreach($restaurants as $restaurant)
                    <a href="/restaurant/{{ $restaurant->id }}" >
                        <flux:card class="hover:bg-zinc-50 dark:hover:bg-zinc-700 mb-4">
                            <div class="flex items-center mb-4">
                                <flux:avatar badge="{{$restaurant->orders()->count()}}" name="{{$restaurant->name}}" color="auto" color:seed="{{ $restaurant->id }}" />
                                <flux:icon name="arrow-up-right" class="ml-auto text-zinc-400" variant="micro" />
                            </div>
                            <flux:heading size="xl">
                                {{$restaurant->name}}
                            </flux:heading>
                            <flux:subheading size="md">{{$restaurant->location}}</flux:subheading>
                        </flux:card>
                    </a>
                    @endforeach
                </div>
            </div>

        @stack('modals')
        @livewireScripts
    </body>
</html>