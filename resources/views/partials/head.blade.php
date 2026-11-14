<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? 'Belajar membaca chart saham' }} · Lilin</title>
<meta name="description" content="Lilin membantu pemula belajar membaca chart saham dari screenshot: tren, support, resistance, dan pola candlestick.">
<link rel="preload" href="{{ asset('fonts/plus-jakarta-sans-600.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/lilin.css') }}?v={{ filemtime(public_path('css/lilin.css')) }}">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 18 28'%3E%3Cpath d='M9 .5c2.6 3 3.2 5.4 0 8-3.2-2.6-2.6-5 0-8z' fill='%23e8a33d'/%3E%3Crect x='8' y='9' width='2' height='18.5' rx='1' fill='%23023933'/%3E%3Crect x='3.5' y='12' width='11' height='11' rx='2' fill='%23023933'/%3E%3C/svg%3E">