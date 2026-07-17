<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fozkudia - Guide</title>

    <link rel="shortcut icon" href="{{ asset('assets/images/k_favicon_32x.png') }}">

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/libs/quill/quill.snow.css') }}">

    @livewireStyles

    <style>
        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #fbfbfd;
            color: #1e2532;
            margin: 0;
        }

        .guide-portal {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Sidebar ─────────────────────────────────────── */
        .guide-sidebar {
            width: 290px;
            min-width: 290px;
            background: #fff;
            border-right: 1px solid #eef0f3;
            box-shadow: 2px 0 10px rgba(15, 23, 42, .03);
            display: flex;
            flex-direction: column;
            padding: 18px 14px 10px;
        }

        .guide-back {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12.5px;
            color: #8a94a6;
            text-decoration: none;
            margin: 0 6px 12px;
            transition: color .15s;
        }

        .guide-back:hover { color: #4c6ef5; }

        .guide-search {
            position: relative;
            margin-bottom: 14px;
        }

        .guide-search input {
            width: 100%;
            border: 1px solid transparent;
            background: #f1f3f7;
            border-radius: 10px;
            padding: 9px 38px 9px 14px;
            font-size: 13.5px;
            color: #1e2532;
            outline: none;
            transition: border-color .15s, background .15s;
        }

        .guide-search input::placeholder { color: #a3adbe; }

        .guide-search input:focus {
            background: #fff;
            border-color: #c7d2f5;
            box-shadow: 0 0 0 3px rgba(76, 110, 245, .08);
        }

        .guide-search .bi {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #a3adbe;
            font-size: 14px;
            pointer-events: none;
        }

        .guide-lang {
            display: flex;
            gap: 8px;
            margin: 0 2px 6px;
        }

        .guide-lang button {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 0;
            background: transparent;
            border-radius: 9px;
            padding: 7px 13px;
            font-size: 13px;
            color: #6b7280;
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        .guide-lang button:hover { background: #f5f7fa; }

        .guide-lang button.active {
            background: #e9efff;
            color: #4c6ef5;
            font-weight: 600;
        }

        .guide-lang svg { border-radius: 50%; flex-shrink: 0; }

        .guide-nav {
            flex: 1;
            overflow-y: auto;
            padding: 4px 2px 16px;
            scrollbar-width: thin;
            scrollbar-color: #d6dbe4 transparent;
        }

        .guide-cat-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: #8a94a6;
            margin: 20px 10px 8px;
        }

        .guide-nav .guide-cat-label:first-child { margin-top: 8px; }

        .guide-nav-item {
            display: block;
            width: 100%;
            text-align: left;
            background: transparent;
            border: 1.5px solid transparent;
            border-radius: 10px;
            padding: 10px 13px;
            margin-bottom: 3px;
            font-size: 14px;
            color: #1e2532;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
        }

        .guide-nav-item:hover { background: #f5f7fa; }

        .guide-nav-item.active {
            background: #f2f6ff;
            color: #4c6ef5;
            font-weight: 600;
            border-color: #16233f;
        }

        .guide-nav-empty {
            text-align: center;
            color: #a3adbe;
            font-size: 13px;
            padding: 28px 12px;
        }

        .guide-nav-empty .bi { font-size: 26px; display: block; margin-bottom: 8px; }

        /* ── Content ─────────────────────────────────────── */
        .guide-main {
            flex: 1;
            overflow-y: auto;
            padding: 42px 52px 70px;
            scroll-behavior: smooth;
        }

        .guide-cat-crumb {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: #98a2b3;
            margin-bottom: 8px;
        }

        .guide-title {
            font-size: 30px;
            font-weight: 700;
            color: #111827;
            margin: 0;
            line-height: 1.25;
        }

        .guide-title::after {
            content: '';
            display: block;
            width: 52px;
            height: 3.5px;
            border-radius: 3px;
            background: #4c6ef5;
            margin-top: 12px;
        }

        .guide-section-card {
            background: #fff;
            border: 1px solid #eef0f3;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(16, 24, 40, .04);
            padding: 30px 36px;
            margin-top: 26px;
        }

        .guide-section-title {
            font-size: 24px;
            font-weight: 700;
            color: #4c6ef5;
            margin: 0 0 20px;
        }

        .guide-block { margin-bottom: 30px; }
        .guide-block:last-child { margin-bottom: 0; }

        .guide-block-title {
            font-size: 18.5px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 4px;
        }

        .guide-block-subtitle {
            font-size: 14.5px;
            color: #6b7280;
            margin: 0 0 12px;
        }

        .guide-rich.ql-editor {
            padding: 0;
            min-height: auto;
            font-size: 15px;
            line-height: 1.75;
            color: #344054;
            white-space: normal;
        }

        .guide-rich.ql-editor h1, .guide-rich.ql-editor h2, .guide-rich.ql-editor h3 {
            color: #1f2937;
            font-weight: 700;
            margin: 18px 0 10px;
        }

        .guide-rich.ql-editor h1 { font-size: 22px; }
        .guide-rich.ql-editor h2 { font-size: 19px; }
        .guide-rich.ql-editor h3 { font-size: 16.5px; }
        .guide-rich.ql-editor p { margin-bottom: 8px; }
        .guide-rich.ql-editor li { margin-bottom: 5px; }
        .guide-rich.ql-editor a { color: #4c6ef5; }

        .guide-rich.ql-editor blockquote {
            border-left: 3px solid #c7d2f5;
            background: #f8faff;
            padding: 10px 16px;
            border-radius: 0 8px 8px 0;
            color: #475467;
        }

        /* ── Welcome / empty state ───────────────────────── */
        .guide-welcome {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #98a2b3;
        }

        .guide-welcome-icon {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: #eef2ff;
            color: #4c6ef5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 20px;
        }

        .guide-welcome h2 {
            font-size: 21px;
            font-weight: 700;
            color: #344054;
            margin-bottom: 8px;
        }

        .guide-welcome p { font-size: 14.5px; max-width: 380px; }

        /* ── Mobile ──────────────────────────────────────── */
        .guide-mobile-toggle {
            display: none;
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 1060;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid #eef0f3;
            background: #fff;
            color: #4c6ef5;
            font-size: 19px;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(16, 24, 40, .1);
            cursor: pointer;
        }

        .guide-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .35);
            z-index: 1040;
        }

        @media (max-width: 991.98px) {
            .guide-mobile-toggle { display: flex; }

            .guide-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 1050;
                transform: translateX(-105%);
                transition: transform .25s ease;
            }

            .guide-portal.sidebar-open .guide-sidebar { transform: none; }
            .guide-portal.sidebar-open .guide-backdrop { display: block; }

            .guide-main { padding: 70px 18px 50px; }
            .guide-section-card { padding: 22px 20px; }
            .guide-title { font-size: 24px; }
            .guide-section-title { font-size: 20px; }
        }
    </style>
</head>

<body>

    {{ $slot }}

    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @livewireScripts
</body>

</html>
