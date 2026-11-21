@php
    $locale = app()->getLocale();
    $logoDark = setting_media('company.logo_dark') ?? setting_media('company.logo_light');
    $companyName = setting('company.name_'.$locale) ?? setting('company.name_en') ?? config('app.name');
    $tagline = \App\Services\Locales\ApprovedContent::tagline($locale);
    // Prefers the primary company location, falling back to the contact
    // settings so a site with no locations recorded is unchanged.
    $address = \App\Support\ContactDetails::address($locale);
    $phone = \App\Support\ContactDetails::phone();
    $email = \App\Support\ContactDetails::email();
    $hours = \App\Services\Locales\ApprovedContent::hours($locale);

    // Optional developer credit after the copyright (Settings → Header & footer); null hides it entirely.
    $credit = \App\Support\DeveloperCredit::get();
    $creditInner = $credit
        ? ($credit['logo'] ? '<img src="'.e($credit['logo']->urlFor(120)).'" alt="" style="height:15px;width:auto" class="inline-block" loading="lazy" decoding="async">' : '').'<span>'.e($credit['name']).'</span>'
        : '';

    $socials = collect([
        ['url' => setting('social.linkedin'), 'label' => 'LinkedIn', 'path' => 'M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9h4v12H3V9Zm7 0h3.8v1.65h.05c.53-.95 1.83-1.95 3.76-1.95C21.6 8.7 23 11 23 14.5V21h-4v-5.8c0-1.4-.03-3.2-2-3.2-2 0-2.3 1.53-2.3 3.1V21h-4V9Z'],
        ['url' => setting('social.facebook'), 'label' => 'Facebook', 'path' => 'M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.5-3.89 3.77-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.45 2.89h-2.33v6.99A10 10 0 0 0 22 12Z'],
        ['url' => setting('social.instagram'), 'label' => 'Instagram', 'path' => 'M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85C2.38 3.92 3.89 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16Zm0 5.68a4.16 4.16 0 1 0 0 8.32 4.16 4.16 0 0 0 0-8.32Zm0 6.86a2.7 2.7 0 1 1 0-5.4 2.7 2.7 0 0 1 0 5.4Zm4.32-7.03a.97.97 0 1 0 0-1.94.97.97 0 0 0 0 1.94Z'],
        ['url' => setting('social.youtube'), 'label' => 'YouTube', 'path' => 'M21.58 7.19a2.5 2.5 0 0 0-1.77-1.77C18.25 5 12 5 12 5s-6.25 0-7.81.42A2.5 2.5 0 0 0 2.42 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .42 4.81 2.5 2.5 0 0 0 1.77 1.77C5.75 19 12 19 12 19s6.25 0 7.81-.42a2.5 2.5 0 0 0 1.77-1.77A26 26 0 0 0 22 12a26 26 0 0 0-.42-4.81ZM10 15V9l5.2 3L10 15Z'],
        ['url' => setting('social.x'), 'label' => 'X', 'path' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231Zm-1.161 17.52h1.833L7.084 4.126H5.117Z'],
    ])->filter(fn (array $s): bool => filled($s['url']));
@endphp

<footer class="bg-forest text-white/70">
    <div class="container-wr py-16 lg:py-20">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-8">

            {{-- Identity --}}
            <div class="lg:col-span-4">
                @if ($logoDark)
                    <img src="{{ $logoDark->urlFor(240) }}" alt="{{ $companyName }}" class="h-14 w-auto">
                @else
                    <span class="text-lg font-bold text-white">{{ $companyName }}</span>
                @endif

                @if ($tagline)
                    <p class="mt-5 max-w-sm text-[0.9375rem] leading-relaxed">{{ $tagline }}</p>
                @endif

                @if ($socials->isNotEmpty())
                    <div class="mt-7">
                        <p class="eyebrow eyebrow-light">{{ __('site.contact.follow_us') }}</p>
                        <div class="mt-4 flex items-center gap-3">
                            @foreach ($socials as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                                   aria-label="{{ $social['label'] }}"
                                   class="flex size-9 items-center justify-center border border-white/15 text-white/70 transition-colors hover:border-gold hover:text-gold">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $social['path'] }}"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Products --}}
            <div class="lg:col-span-2">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('site.footer.products') }}</h2>
                <ul class="mt-5 space-y-3 text-[0.9375rem]">
                    @forelse ($footerProducts ?? collect() as $item)
                        <li><a href="{{ $item->href($locale) }}" class="transition-colors hover:text-gold">{{ $item->label($locale) }}</a></li>
                    @empty
                        <li><a href="{{ route('products.index') }}" class="transition-colors hover:text-gold">{{ __('site.nav.products') }}</a></li>
                        <li><a href="{{ route('packaging.index') }}" class="transition-colors hover:text-gold">{{ __('site.nav.packaging') }}</a></li>
                    @endforelse
                </ul>
            </div>

            {{-- Company --}}
            <div class="lg:col-span-2">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('site.footer.company') }}</h2>
                <ul class="mt-5 space-y-3 text-[0.9375rem]">
                    @forelse ($footerCompany ?? collect() as $item)
                        <li><a href="{{ $item->href($locale) }}" class="transition-colors hover:text-gold">{{ $item->label($locale) }}</a></li>
                    @empty
                        <li><a href="{{ route('about') }}" class="transition-colors hover:text-gold">{{ __('site.nav.about') }}</a></li>
                        <li><a href="{{ route('quality') }}" class="transition-colors hover:text-gold">{{ __('site.nav.quality') }}</a></li>
                        <li><a href="{{ route('export') }}" class="transition-colors hover:text-gold">{{ __('site.nav.export') }}</a></li>
                        <li><a href="{{ route('private-label') }}" class="transition-colors hover:text-gold">{{ __('site.nav.private_label') }}</a></li>
                        <li><a href="{{ route('news.index') }}" class="transition-colors hover:text-gold">{{ __('site.nav.news') }}</a></li>
                    @endforelse
                </ul>
            </div>

            {{-- Contact --}}
            <div class="lg:col-span-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('site.footer.contact') }}</h2>
                <ul class="mt-5 space-y-4 text-[0.9375rem]">
                    @if ($address)
                        <li class="flex gap-3">
                            <svg class="mt-0.5 size-4 flex-none text-gold" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.69 18.933A1 1 0 0 0 10 19a1 1 0 0 0 .31-.067c2.71-1.13 7.19-4.9 7.19-10.183a7.5 7.5 0 0 0-15 0c0 5.283 4.48 9.053 7.19 10.183ZM10 11.25a2.75 2.75 0 1 0 0-5.5 2.75 2.75 0 0 0 0 5.5Z" clip-rule="evenodd"/></svg>
                            <span class="whitespace-pre-line">{{ $address }}</span>
                        </li>
                    @endif
                    @if ($phone)
                        <li class="flex gap-3">
                            <svg class="mt-0.5 size-4 flex-none text-gold" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.5 11.5 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-7.18 0-13-5.82-13-13v-1.5Z" clip-rule="evenodd"/></svg>
                            <a href="{{ \App\Support\ContactDetails::telHref($phone) }}" dir="ltr" class="transition-colors hover:text-gold">{{ $phone }}</a>
                        </li>
                    @endif
                    @if ($email)
                        <li class="flex gap-3">
                            <svg class="mt-0.5 size-4 flex-none text-gold" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 4a2 2 0 0 0-2 2v.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 6.161V6a2 2 0 0 0-2-2H3Z"/><path d="m19 7.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 7.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.839Z"/></svg>
                            <a href="mailto:{{ $email }}" dir="ltr" class="break-anywhere transition-colors hover:text-gold">{{ $email }}</a>
                        </li>
                    @endif
                    @if ($hours)
                        <li class="flex gap-3">
                            <svg class="mt-0.5 size-4 flex-none text-gold" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .28.16.54.41.67l3 1.5a.75.75 0 1 0 .68-1.34L10.75 9.54V5Z" clip-rule="evenodd"/></svg>
                            <span>{{ $hours }}</span>
                        </li>
                    @endif
                </ul>

                <a href="{{ route('quote.create') }}" class="btn btn-gold mt-7">{{ __('site.cta.request_quote') }}</a>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-wr flex flex-col gap-4 py-6 text-[0.8125rem] sm:flex-row sm:items-center sm:justify-between">
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span>&copy; {{ now()->year }} {{ $companyName }}. {{ __('site.footer.rights') }}</span>
                @if ($credit)
                    <span class="text-white/30" aria-hidden="true">|</span>
                    <span class="inline-flex items-center gap-1.5">
                        <span>{{ __('chrome.footer.developed_by') }}</span>
                        @if ($credit['url'])
                            <a href="{{ $credit['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-white transition-colors hover:text-gold">{!! $creditInner !!}</a>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-white">{!! $creditInner !!}</span>
                        @endif
                    </span>
                @endif
            </p>
            {{-- Legal links appear only once the page behind them has been
                 marked reviewed. Linking to an unreviewed Privacy Policy from
                 every page would publish it in all but name. --}}
            @php($legalPages = \App\Models\Page::publiclyLinkableLegalPages())
            @if ($legalPages->isNotEmpty())
                <ul class="flex flex-wrap items-center gap-x-6 gap-y-2">
                    @foreach ($legalPages as $legalPage)
                        <li>
                            <a href="{{ route($legalPage['key'] === 'privacy' ? 'privacy' : 'terms') }}"
                               class="transition-colors hover:text-gold">{{ $legalPage['title'] }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</footer>
