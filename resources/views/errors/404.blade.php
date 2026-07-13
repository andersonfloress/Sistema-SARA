<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada | IE Santa Rosa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center px-4">
        <div class="w-24 h-24 bg-indigo-100 rounded-3xl flex items-center justify-center mx-auto mb-6">
            <i data-lucide="search-x" class="w-12 h-12 text-indigo-500"></i>
        </div>
        <h1 class="text-8xl font-bold text-gray-200 mb-4">404</h1>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Página no encontrada</h2>
        <p class="text-gray-500 mb-8">La página que buscas no existe o fue movida.</p>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded font-medium hover:bg-indigo-700 transition">
            Regresar
        </a>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
