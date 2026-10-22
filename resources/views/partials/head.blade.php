<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

@if (filled($description ?? null))
    <meta name="description" content="{{ $description }}">
@endif

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<script>
    (function () {
        var mode = localStorage.getItem('appearance') || 'system';
        var dark = mode === 'dark' || (mode !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    })();
</script>

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    document.addEventListener('alpine:init', function () {
        Alpine.store('appearance', {
            mode: localStorage.getItem('appearance') || 'system',

            get dark() {
                return this.mode === 'dark' || (this.mode !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            },

            init() {
                this.apply();

                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                    if (this.mode === 'system') this.apply();
                });
            },

            set(mode) {
                this.mode = mode;
                localStorage.setItem('appearance', mode);
                this.apply();
            },

            apply() {
                document.documentElement.classList.toggle('dark', this.dark);
                document.documentElement.style.colorScheme = this.dark ? 'dark' : 'light';
            },
        });

        Alpine.store('sidebar', {
            open: false,
        });
    });
</script>
