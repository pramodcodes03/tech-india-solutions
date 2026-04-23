<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset='utf-8' />
    <meta http-equiv='X-UA-Compatible' content='IE=edge' />
    <title>{{ $title ?? 'Employee Portal' }}</title>
    <meta name='viewport' content='width=device-width, initial-scale=1' />
    <link rel="icon" type="image/svg" href="/assets/images/logo.png" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="/assets/js/perfect-scrollbar.min.js"></script>
    <script defer src="/assets/js/popper.min.js"></script>
    <script defer src="/assets/js/tippy-bundle.umd.min.js"></script>
    <script defer src="/assets/js/sweetalert.min.js"></script>
    @vite(['resources/css/app.css'])
</head>
<body x-data="main" class="relative overflow-x-hidden text-sm antialiased font-normal font-nunito"
    :class="[$store.app.sidebar ? 'toggle-sidebar' : '', $store.app.theme === 'dark' || $store.app.isDarkMode ? 'dark' : '',
        $store.app.menu, $store.app.layout, $store.app.rtlClass]">

    <div x-cloak class="fixed inset-0 bg-[black]/60 z-50 lg:hidden" :class="{ 'hidden': !$store.app.sidebar }"
        @click="$store.app.toggleSidebar()"></div>

    <div class="min-h-screen text-black main-container dark:text-white-dark" :class="[$store.app.navbar]">
        <x-employee.sidebar />

        <div class="flex flex-col min-h-screen main-content">
            <x-employee.header />

            <div class="p-6 dvanimation animate__animated" :class="[$store.app.animation]">
                @if(session('success'))
                    <div class="mb-4 px-4 py-3 rounded-lg bg-success/10 text-success border border-success/30 flex items-start gap-2">
                        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 px-4 py-3 rounded-lg bg-danger/10 text-danger border border-danger/30 flex items-start gap-2">
                        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM9 5a1 1 0 0 1 2 0v5a1 1 0 1 1-2 0V5zm1 9a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" clip-rule="evenodd"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-4 px-4 py-3 rounded-lg bg-danger/10 text-danger border border-danger/30">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>

            <footer class="p-4 text-center text-xs text-gray-500">© {{ date('Y') }} Tech India Solutions · Employee Portal</footer>
        </div>
    </div>

    <script src="/assets/js/alpine-collaspe.min.js"></script>
    <script src="/assets/js/alpine-persist.min.js"></script>
    <script defer src="/assets/js/alpine-ui.min.js"></script>
    <script defer src="/assets/js/alpine-focus.min.js"></script>
    <script defer src="/assets/js/alpine.min.js"></script>
    <script src="/assets/js/custom.js"></script>
    @stack('scripts')
</body>
</html>
