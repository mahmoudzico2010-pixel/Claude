@php
    $locale = app()->getLocale();
    $dir = locale_dir($locale);
    $meta = ($seo ?? \App\Services\Seo\SeoData::make())->toArray();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title>{{ $meta['title'] }}</title>

    @if ($meta['description'])
        <meta name="description" content="{{ $meta['description'] }}">
    @endif

    @if ($meta['noindex'] || ! \App\Support\Locales::isPublished($locale))
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large">
    @endif

    <link rel="canonical" href="{{ $meta['canonical'] }}">

    {{-- Each language this page genuinely exists in, itself included, plus x-default; all on the preferred host. --}}
    @foreach ($meta['alternates'] as $hreflang => $href)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
    @endforeach

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="{{ $meta['type'] }}">
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:url" content="{{ $meta['canonical'] }}">
    <meta property="og:locale" content="{{ \App\Support\Locales::ogLocale($locale) }}">
    @if ($meta['description'])
        <meta property="og:description" content="{{ $meta['description'] }}">
    @endif
    @if ($meta['image'])
        <meta property="og:image" content="{{ $meta['image'] }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $meta['image'] }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $meta['title'] }}">

    <meta name="theme-color" content="#173F35">

    @php($favicon = setting_media('company.favicon'))
    @if ($favicon)
        <link rel="icon" href="{{ $favicon->url() }}">
        <link rel="apple-touch-icon" href="{{ $favicon->urlFor(180) }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- WCAG 2.2 target size (24 px minimum) for text links and small buttons; it changes no visible layout. --}}
    <style>header a,header button,main article a.block,footer a{min-height:24px}header nav a,header>div a.flex{display:inline-flex;align-items:center}footer a{display:inline-block}</style>

    {{-- Active colour theme.

         After @vite on purpose: Tailwind declares the design tokens on :root,
         and this block re-declares the handful the preset changes. Later wins,
         so every utility referencing them re-colours with no rebuild.

         Printed unescaped because it is CSS, not markup — App\Support\Theme
         validates every property name and value against a strict pattern and
         drops anything that does not match, which is what makes that safe. --}}
    @php($themeCss = \App\Support\Theme::css())
    @if ($themeCss !== '')
        <style>{!! $themeCss !!}</style>
    @endif
    {{-- Two text tokens kept at WCAG AA (4.5:1) contrast even when a theme preset sets lighter ones. --}}
    <style>:root{--color-ink-subtle:#646b65;--color-olive-muted:#5c6a36}</style>

    {{-- Structured data, one script per schema so a malformed block cannot
         invalidate the rest. --}}
    @foreach ($meta['schemas'] as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach

    {{-- Phone-only experience (<= 599 px). The stylesheet is applied by the browser only at that width; nothing above it changes. --}}
    @php($wrmCss = public_path('css/wr-mobile.css'))
    @if (is_file($wrmCss))
        <style media="(max-width: 599px)">{!! file_get_contents($wrmCss) !!}</style>
        <script src="{{ asset('js/wr-mobile.js') }}?v={{ filemtime(public_path('js/wr-mobile.js')) }}" defer></script>
    @endif

    @include('partials.analytics')
    @include('partials.wr-analytics')

    @pluginSlot('public.head')
    @stack('head')
</head>
{{-- Theme::bodyClasses() returns only values drawn from a fixed set — see the
     filter at the end of that method — so it carries no administrator text
     into the class attribute. --}}
<body class="min-h-screen bg-surface antialiased {{ \App\Support\Theme::bodyClasses() }}">
    @if (app()->isProduction() && ($gtmId = setting('analytics.gtm_id') ?: config('wadi.analytics.gtm_id')))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

@stack('body-start')
@include('partials.wr-consent')
@pluginSlot('public.body_start')

@include('partials.preloader')


    <a href="#main" class="skip-link">{{ __('site.skip_to_content') }}</a>

    @if (is_file($wrmCss))
        @include('partials.mobile-shell')
    @endif

    @include('partials.header')

    <main id="main">
        @yield('content')
        @php($modulePage = array_search(request()->route()?->getName(), (array) config('modules.contributions.pages'), true))
        @if (is_string($modulePage))
            <x-module-slot target="page" :place="$modulePage" />
        @endif
        {{ $slot ?? '' }}
    </main>

    @include('partials.footer')
    @pluginSlot('public.footer')

    @include('partials.whatsapp')

    @pluginSlot('public.body_end')
    @stack('scripts')
</body>
</html>
