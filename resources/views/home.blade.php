<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'VIA BAN') }} - Home</title>

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap"
        rel="stylesheet">

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-950 text-white font-[instrument-sans] antialiased">

    <!-- Header -->
    <header class="bg-gray-900 border-b border-gray-800 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center px-6">
            <h1 class="text-2xl font-bold text-amber-400">{{ config('app.name', 'VIA BAN') }}</h1>
            <nav>
                <a href="/" class="text-gray-300 hover:text-amber-400 px-3 transition">Home</a>
                <a href="/admin" class="text-gray-300 hover:text-amber-400 px-3 transition">Admin</a>
                <a href="/contact" class="text-gray-300 hover:text-amber-400 px-3 transition">Contact</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-12 px-6">
        <h2 class="text-3xl font-semibold mb-8 text-center">Dashboard Umum</h2>

        <!-- Card Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

            <!-- Card 1 -->
            <div
                class="bg-gray-900 border border-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-amber-500/20 hover:-translate-y-1 transition-all duration-300">
                <div
                    class="flex items-center justify-center w-16 h-16 rounded-xl bg-amber-500/10 text-amber-400 mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m4-8v8m5-8l2 2m0 0l-7 7-7-7" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-center">Data Bengkel</h3>
                <p class="text-gray-400 text-center mb-4">Lihat dan kelola daftar bengkel yang terdaftar di sistem.</p>
                <div class="text-center">
                    <a href="/admin/tb-tokos"
                        class="bg-amber-500 hover:bg-amber-600 text-black font-semibold px-4 py-2 rounded-full transition">
                        Kelola
                    </a>
                </div>
            </div>

            <!-- Card 2 -->
            <div
                class="bg-gray-900 border border-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-amber-500/20 hover:-translate-y-1 transition-all duration-300">
                <div
                    class="flex items-center justify-center w-16 h-16 rounded-xl bg-amber-500/10 text-amber-400 mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 8c-2.21 0-4 1.79-4 4v5h8v-5c0-2.21-1.79-4-4-4z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 19h12M4 19h16" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-center">Laporan Limbah</h3>
                <p class="text-gray-400 text-center mb-4">Pantau jumlah dan jenis limbah yang dikumpulkan setiap
                    periode.</p>
                <div class="text-center">
                    <a href="/admin/laporan"
                        class="bg-amber-500 hover:bg-amber-600 text-black font-semibold px-4 py-2 rounded-full transition">
                        Lihat Laporan
                    </a>
                </div>
            </div>

            <!-- Card 3 -->
            <div
                class="bg-gray-900 border border-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-amber-500/20 hover:-translate-y-1 transition-all duration-300">
                <div
                    class="flex items-center justify-center w-16 h-16 rounded-xl bg-amber-500/10 text-amber-400 mb-4 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18v18H3V3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 8h8v8H8z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2 text-center">Akun Pengguna</h3>
                <p class="text-gray-400 text-center mb-4">Atur akun admin dan pengepul dengan hak akses yang sesuai.</p>
                <div class="text-center">
                    <a href="/admin/users"
                        class="bg-amber-500 hover:bg-amber-600 text-black font-semibold px-4 py-2 rounded-full transition">
                        Kelola Akun
                    </a>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center text-gray-500 py-6 border-t border-gray-800">
        &copy; {{ date('Y') }} CV. VIA BAN. Semua hak cipta dilindungi.
    </footer>

</body>

</html>