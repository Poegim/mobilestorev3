<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.png" sizes="any">

@fonts

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<script>
    // Default to dark mode if user has no preference saved yet
    if (!localStorage.getItem('flux-appearance')) {
        localStorage.setItem('flux-appearance', 'dark');
    }
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    // Force dark mode — light mode disabled intentionally
    document.documentElement.classList.add('dark');
</script>

{{-- @fluxAppearance --}}
