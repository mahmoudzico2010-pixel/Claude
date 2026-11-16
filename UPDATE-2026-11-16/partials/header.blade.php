@php
    $locale = app()->getLocale();
    $logo = setting_media('company.logo_light');
    $logoDark = setting_media('company.logo_dark') ?? $logo;
    // Languages with long menu labels (Spanish, French, Portuguese, Greek, Russian) use tighter spacing and a slightly
    // smaller size between 1280 and 1535 px, so the desktop menu never pushes the page wider than the screen.
    $compactNav = in_array($locale, ['es', 'fr', 'pt', 'el', 'ru', 'it'], true);
    $navText = $compactNav ? 'text-[0.8125rem] 2xl:text-[0.9375rem]' : 'text-[0.9375rem]';
    $companyName = setting('company.short_name_'.$locale) ?? setting('company.short_name_en') ?? config('app.name');

    // Falls back to the built-in routes when no menu has been configured yet,
    // so the site is navigable on a fresh install.
    // Any depth (limited by wadi.navigation.max_depth when the menu is built).
    $toTree = function ($children) use (&$toTree, $locale): array {
        return collect($children)->map(fn ($c) => [
            'label' => $c->label($locale),
            'href' => $c->href($locale),
            'new_tab' => $c->open_in_new_tab,
            'children' => $toTree($c->children ?? []),
        ])->all();
    };

    $items = ($headerMenu ?? collect())->isNotEmpty()
        ? ($headerMenu ?? collect())->map(fn ($i) => [
            'label' => $i->label($locale),
            'href' => $i->href($locale),
            'active' => url()->current() === $i->href($locale),
            'new_tab' => $i->open_in_new_tab,
            'children' => $toTree($i->children ?? []),
        ])
        : collect([
            // Mirrors NavigationSeeder. A fresh install with no menu rows still
            // needs a way home, and this fallback is what renders until the
            // seeder has run.
            ['label' => __('site.nav.home'), 'href' => route('home'), 'active' => request()->routeIs('home'), 'new_tab' => false],
            ['label' => __('site.nav.products'), 'href' => route('products.index'), 'active' => request()->routeIs('products.*'), 'new_tab' => false],
            ['label' => __('site.nav.packaging'), 'href' => route('packaging.index'), 'active' => request()->routeIs('packaging.*'), 'new_tab' => false],
            ['label' => __('site.nav.about'), 'href' => route('about'), 'active' => request()->routeIs('about'), 'new_tab' => false],
            ['label' => __('site.nav.quality'), 'href' => route('quality'), 'active' => request()->routeIs('quality'), 'new_tab' => false],
            ['label' => __('site.nav.export'), 'href' => route('export'), 'active' => request()->routeIs('export'), 'new_tab' => false],
            ['label' => __('site.nav.private_label'), 'href' => route('private-label'), 'active' => request()->routeIs('private-label'), 'new_tab' => false],
            ['label' => __('site.nav.news'), 'href' => route('news.index'), 'active' => request()->routeIs('news.*'), 'new_tab' => false],
            ['label' => __('site.nav.contact'), 'href' => route('contact.show'), 'active' => request()->routeIs('contact.*'), 'new_tab' => false],
        ]);
@endphp

<header
    x-data="siteHeader"
    {{-- Opaque at rest, glass once the page scrolls under it. Frosting a
         header that is sitting on plain white achieves nothing but GPU work,
         so the effect is only paid for when there is something behind it. --}}
    class="sticky top-0 z-[var(--z-navigation)] transition-[background-color,box-shadow,backdrop-filter] duration-300"
    :class="scrolled
        ? 'glass glass-edge shadow-[0_4px_24px_-12px_rgba(32,37,33,0.18)]'
        : 'bg-white border-b border-line'"
>
    {{-- Utility strip: contact details and language, desktop only --}}
    <div class="hidden border-b border-line/70 bg-surface-alt lg:block">
        <div class="container-wr flex min-h-[2.75rem] items-center justify-between py-0.5 text-[0.8125rem] text-ink-muted">
            <div class="flex items-center gap-6">
                @if (setting('contact.phone'))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('contact.phone')) }}"
                       class="flex items-center gap-2 transition-colors hover:text-olive-deep">
                        <svg class="size-3.5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.5 11.5 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-7.18 0-13-5.82-13-13v-1.5Z" clip-rule="evenodd"/></svg>
                        <span dir="ltr">{{ setting('contact.phone') }}</span>
                    </a>
                @endif
                @if (setting('contact.email'))
                    <a href="mailto:{{ setting('contact.email') }}"
                       class="flex items-center gap-2 transition-colors hover:text-olive-deep">
                        <svg class="size-3.5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 4a2 2 0 0 0-2 2v.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 6.161V6a2 2 0 0 0-2-2H3Z"/><path d="m19 7.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 7.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.839Z"/></svg>
                        <span dir="ltr">{{ setting('contact.email') }}</span>
                    </a>
                @endif
            </div>

            <div class="flex items-center"><x-language-switcher variant="desktop" /></div>
        </div>
    </div>

    {{-- Main bar --}}
    <div class="container-wr">
        <div class="flex items-center justify-between gap-6 transition-[height] duration-200"
             :class="scrolled ? 'h-16' : 'h-20'">

            <a href="{{ route('home') }}" class="flex flex-none items-center gap-3" aria-label="{{ $companyName }}">
                @if ($logo)
                    <img src="{{ $logo->urlFor(240) }}"
                         alt="{{ $companyName }}"
                         class="w-auto transition-[height] duration-200"
                         :class="scrolled ? 'h-10' : 'h-12'"
                         width="{{ $logo->width }}" height="{{ $logo->height }}">
                @else
                    <span class="text-lg font-bold tracking-tight text-olive-deep">{{ $companyName }}</span>
                @endif
            </a>

            {{-- Desktop navigation --}}
            <nav class="hidden items-center {{ $compactNav ? 'gap-3 2xl:gap-7' : 'gap-7' }} xl:flex" aria-label="{{ __('site.menu') }}">
                @foreach ($items as $item)
                    @php($children = $item['children'] ?? [])

                    @if ($children === [])
                        <a href="{{ $item['href'] }}"
                           @if ($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif
                           @class([
                               'relative py-2 '.$navText.' font-medium transition-colors',
                               'underline-sweep' => ! $item['active'],
                               'text-olive-deep' => $item['active'],
                               'text-ink-muted hover:text-olive-deep' => ! $item['active'],
                           ])
                           @if ($item['active']) aria-current="page" @endif
                        >
                            {{ $item['label'] }}
                            @if ($item['active'])
                                <span class="absolute inset-x-0 -bottom-px h-0.5 bg-gold" aria-hidden="true"></span>
                            @endif
                        </a>
                    @else
                        {{-- A parent with children.

                             Opens on hover for a pointer and on click for
                             everything else, and the trigger is a real button
                             so it is reachable by keyboard. Hover alone would
                             make the submenu unreachable without a mouse. --}}
                        <div class="relative"
                             x-data="{ open: false, timer: null }"
                             x-on:mouseenter="clearTimeout(timer); open = true"
                             x-on:mouseleave="timer = setTimeout(() => { open = false }, 220)"
                             x-on:focusin="open = true"
                             x-on:focusout="open = false">

                            <button type="button"
                                    x-on:click="open = true"
                                    x-on:keydown.escape="open = false"
                                    x-on:keydown.arrow-down.prevent="open = true; openAndFocus($el)"
                                    :aria-expanded="open.toString()"
                                    @class([
                                        'relative flex items-center gap-1 py-2 '.$navText.' font-medium transition-colors',
                                        'underline-sweep' => ! $item['active'],
                                        'text-olive-deep' => $item['active'],
                                        'text-ink-muted hover:text-olive-deep' => ! $item['active'],
                                    ])>
                                {{ $item['label'] }}
                                <svg class="size-3.5 transition-transform" :class="open && 'rotate-180'"
                                     viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="m5 8 5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>

                            {{-- `start-0` not `left-0`: the submenu hangs from
                                 the reading-start edge, so it mirrors in
                                 Arabic without a second rule. --}}
                            <div x-show="open"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 data-menu-list
                                 x-on:keydown="menuKey($event)"
                                 x-on:keydown.escape.stop="open = false; $el.parentElement.querySelector('button')?.focus()"
                                 class="absolute start-0 top-full z-[var(--z-navigation)] min-w-52 border border-line bg-surface py-2 shadow-raised">
                                @include('partials.nav-dropdown-items', ['entries' => $children, 'level' => 1])
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="flex flex-none items-center gap-3">
                <a href="{{ route('quote.create') }}" class="btn btn-primary hidden sm:inline-flex">
                    {{ __('site.cta.request_quote') }}
                </a>

                {{-- Mobile toggle --}}
                <button type="button"
                        @click="toggleMobile()"
                        class="-me-2 p-2 text-ink xl:hidden"
                        :aria-expanded="mobileOpen.toString()"
                        aria-controls="mobile-menu"
                        aria-label="{{ __('site.menu') }}">
                    <svg x-show="!mobileOpen" class="size-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="size-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="mobileOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         id="mobile-menu"
         x-on:keydown.escape.window="if (mobileOpen) { closeMobile(); $nextTick(() => document.querySelector('[aria-controls=mobile-menu]')?.focus()) }"
         class="border-t border-line bg-white xl:hidden"
         x-trap.noscroll="mobileOpen">
        <nav class="container-wr flex max-h-[calc(100dvh-5rem)] flex-col overflow-y-auto py-4" aria-label="{{ __('site.menu') }}">
            @foreach ($items as $item)
                @if (empty($item['children']))
                    <a href="{{ $item['href'] }}"
                       @click="closeMobile()"
                       @class([
                           'border-b border-line py-3.5 text-base font-medium transition-colors',
                           'text-olive-deep' => $item['active'],
                           'text-ink hover:text-olive-deep' => ! $item['active'],
                       ])>{{ $item['label'] }}</a>
                @else
                    {{-- Accordion: the label is a link to the page, the chevron a separate button that expands the group. --}}
                    <div x-data="{ open: {{ $item['active'] ? 'true' : 'false' }} }" class="border-b border-line">
                        <div class="flex items-stretch">
                            <a href="{{ $item['href'] }}" @click="closeMobile()"
                               @class(['flex-1 py-3.5 text-base font-medium transition-colors', 'text-olive-deep' => $item['active'], 'text-ink hover:text-olive-deep' => ! $item['active']])>{{ $item['label'] }}</a>
                            <button type="button" x-on:click="open = ! open" :aria-expanded="open.toString()"
                                    aria-label="{{ __('catalog.nav.toggle', ['name' => $item['label']]) }}"
                                    class="flex w-12 flex-none items-center justify-center text-ink-muted hover:text-olive-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-gold">
                                <svg class="size-4 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 8 5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <div x-show="open" x-cloak class="flex flex-col">
                            @include('partials.nav-mobile-items', ['entries' => $item['children'], 'level' => 0])
                        </div>
                    </div>
                @endif
            @endforeach

            <a href="{{ route('quote.create') }}" class="btn btn-primary mt-5">{{ __('site.cta.request_quote') }}</a>

            <div class="mt-5 border-t border-line pt-5">
                <span class="mb-2 block text-xs text-ink-subtle">{{ __('site.language') }}</span>
                <x-language-switcher variant="mobile" />
            </div>
        </nav>
    </div>
</header>
