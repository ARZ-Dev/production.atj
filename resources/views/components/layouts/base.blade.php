<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8" />
    {{-- <title>@yield('title', ' | FabKin Admin & Dashboards Template')</title> --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    {{-- Read by submitDelete() in app.js --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{--
    <meta content="Admin & Dashboards Template" name="description" />
    <meta content="Pixeleyez" name="author" /> --}}
    @php
    $title = ucwords(str_replace('.', '-', request()->route()->getName()));
    $title = ucwords(str_replace('-', ' ', $title));
    @endphp

    <title>Fozkudia - Production - {{ $title }}</title>

    <!-- layout setup -->
    <script type="module" src="{{ asset('assets/js/layout-setup.js') }}"></script>

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/k_favicon_32x.png') }}?v={{ @filemtime(public_path('assets/images/k_favicon_32x.png')) ?: 1 }}">

    @yield('css')
    @include('partials.head-css')

    @livewireStyles
</head>

<body>

    {{-- @yield('content') --}}

    {{ $slot }}


    @yield('js')
    @include('partials.vendor-scripts')
    @stack('scripts')
    @livewireScripts
    <script>
        // wire:submit disables a form only while its request runs; the response
        // that redirects away re-enables it, and the page stays clickable until
        // the next one arrives — so a second click would send the form again.
        // Once a component is on its way out, keep its controls disabled. (The
        // server refuses a repeat anyway — PreventsDuplicateSubmission.)
        document.addEventListener('livewire:init', () => {
            Livewire.hook('commit', ({ component, succeed }) => {
                succeed(({ effects }) => {
                    if (!effects || !effects.redirect) return;

                    component.el.querySelectorAll('button, input, select, textarea')
                        .forEach(el => { el.disabled = true; });
                });
            });
        });

        window.addEventListener('swal:confirm', event => {
            Swal.fire({
                title: event.detail[0].title,
                text: event.detail[0].text,
                icon: event.detail[0].type,
                showCancelButton: true,
                confirmButtonText: event.detail[0].buttonText ?? 'Yes, delete it!',
                customClass: {
                    confirmButton: 'btn btn-primary me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            })
            .then((willDelete) => {
                if (willDelete.isConfirmed) {
                    window.livewire.emit(event.detail[0].method, event.detail[0].id);
                }
            });
        });

        window.addEventListener('swal:success', event => {
            Swal.fire({
                title: event.detail[0].title,
                text: event.detail[0].text,
                icon: 'success',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        });

        window.addEventListener('swal:error', event => {
            Swal.fire({
                title: event.detail[0].title,
                html: event.detail[0].text,
                icon: 'error',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false,
            });
        });

        // $('.flatpickr-date').flatpickr({
        //     monthSelectorType: 'static'
        // });
    </script>

    @if (Session::has('success'))
    <script>
        toastr.success("{{ Session::get('success') }}", "Success", {
                positionClass: "toast-top-right",
                progressBar: true,
                timeOut: 3000,
                extendedTimeOut: 2000,
                closeButton: true,
            });
    </script>
    @endif

    @if (Session::has('error'))
    <script>
        toastr.error("{{ Session::get('error') }}", "Error", {
                positionClass: "toast-top-right",
                progressBar: true,
                timeOut: 3000,
                extendedTimeOut: 2000,
                closeButton: true,
            });
    </script>
    @endif

</body>

</html>