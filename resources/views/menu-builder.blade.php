<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0e0e0e" media="(prefers-color-scheme: dark)">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <title>{{ $order->title }} · {{ $restaurant->name }}</title>
    @viteReactRefresh
    @vite('resources/js/menu-builder/main.jsx')
</head>
<body>
    <div id="menu-builder" data-props='@json($props)'></div>
    <noscript>The menu builder needs JavaScript. <a href="{{ route('orders.show', $order) }}">View this order</a> instead.</noscript>
</body>
</html>
