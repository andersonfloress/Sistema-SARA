<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar — Sistema SARA</title>
    <link rel="icon" type="image/png" href="/images/logo-transparent.png">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        tailwind.config = {
            theme: {
                fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
            }
        }
    </script>
</head>
<body class="min-h-screen font-sans antialiased flex items-center justify-center p-4"
      style="background: linear-gradient(135deg, #1a0308 0%, #3d0812 40%, #5c1020 70%, #2d0609 100%);">

    {{-- Patrón de fondo sutil --}}
    <div class="absolute inset-0 opacity-10"
         style="background-image: radial-gradient(circle at 25% 25%, #ffffff 1px, transparent 1px),
                                  radial-gradient(circle at 75% 75%, #ffffff 1px, transparent 1px);
                background-size: 40px 40px;"></div>

    <div class="relative z-10 w-full">
        @yield('content')
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
