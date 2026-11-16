@extends('layouts.public')

@php
    $frameData = \App\Support\ProductFrames::for($product);
    $gallery = $product->galleryImages();
    $specs = $product->publicSpecifications();
@endphp

@section('content')
    <div class="border-b border-line bg-surface-alt">
        <div class="container-wr py-5">
            <x-breadcrumbs :items="$seo->breadcrumbs" />
        </div>
    </div>

    <section class="py-12 lg:py-16">
        <div class="container-wr">
            <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">

                {{-- Gallery --}}
                <div x-data="productGallery({{ Js::from($gallery->map(fn ($m) => [
                    'url' => $m->urlFor(1280),
                    'srcset' => $m->srcset(),
                    'alt' => $m->alt(),
                    'thumb' => $m->urlFor(200),
                ])->values()) }})">
                    @if ($gallery->isNotEmpty())
                        {{-- Shared media frame, square. `contain` rather than
                             `cover`: this is the customer's closest look at the
                             product, and cropping a jar's lid off here is the
                             worst place on the site to do it.

                             The hover treatment here is a real magnifier, not
                             the `media-frame-zoom` scale the catalogue cards
                             use. Scaling the displayed image by a few per cent
                             makes it bigger and reveals nothing — the pixels on
                             screen are the same pixels. The layer below paints
                             a genuinely larger source and pans it with the
                             cursor, so a customer can read a label.

                             The frame clips, so the enlargement never reaches
                             the title, the thumbnails or anything beside them,
                             and no layout moves. --}}
                        <div class="media-frame {{ $frameData ? '' : 'media-frame-1-1' }} media-frame-magnify {{ $frameData ? 'bg-white' : 'border border-line' }}" @if ($frameData) style="--media-frame-ratio: {{ $frameData['ratio'] }}" @endif data-framed="{{ $frameData ? '1' : '0' }}"
                             x-on:mouseenter="enter($event)"
                             x-on:mousemove="move($event)"
                             x-on:mouseleave="leave()">

                            <template x-if="current">
                                <img :src="current.url"
                                     :srcset="current.srcset"
                                     :alt="current.alt"
                                     sizes="(min-width: 1024px) 40rem, 92vw"
                                     @if ($frameData) style="object-fit: contain; padding: {{ $frameData['padding'] }}%; box-sizing: border-box" @endif
                                     fetchpriority="high" decoding="async">
                            </template>

                            @if ($frameData)
                                {{-- The decorative frame sits above the photo and the magnifier, never intercepts clicks, and leaves the
                                     "view larger" button (z-10), swipe and thumbnails working. Originals and the lightbox are untouched. --}}
                                <img src="{{ $frameData['url'] }}" alt="" aria-hidden="true" class="wr-frame-overlay"
                                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:fill;pointer-events:none;z-index:5;padding:0">
                            @endif

                            {{-- encodeURI on a path we generated ourselves: the
                                 value lands in a style attribute, and url() is
                                 the one place a stray quote would matter.

                                 The style is bound as an OBJECT on purpose. A
                                 string binding replaces the whole style
                                 attribute, which erases the `display:none` that
                                 x-show applies — after a thumbnail change the
                                 white layer would then sit over the product
                                 until the next hover. --}}
                            <div class="media-zoom-layer"
                                 x-show="zooming"
                                 x-cloak
                                 :style="{ backgroundImage: `url('${encodeURI(zoomSource)}')`,
                                           backgroundSize: backgroundSize,
                                           backgroundPosition: backgroundPosition }"
                                 aria-hidden="true"></div>

                            {{-- Touch and keyboard reach the same detail here.
                                 A magnifier that only answers to a moving mouse
                                 leaves everyone else unable to look closely. --}}
                            <button type="button"
                                    x-on:click.stop="openLightbox()"
                                    x-show="! zooming"
                                    class="absolute bottom-3 end-3 z-10 inline-flex items-center gap-1.5 rounded-badge
                                           bg-surface/90 px-2.5 py-1.5 text-xs font-medium text-ink shadow-card
                                           backdrop-blur-sm transition-colors hover:bg-surface
                                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-olive-deep">
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                     stroke-width="1.7" aria-hidden="true">
                                    <circle cx="9" cy="9" r="6"/>
                                    <path d="M13.5 13.5 17 17M9 6.5v5M6.5 9h5" stroke-linecap="round"/>
                                </svg>
                                {{ __('site.products.view_larger') }}
                            </button>
                        </div>

                        @if ($gallery->count() > 1)
                            <ul class="mt-4 grid grid-cols-5 gap-3" role="list" aria-label="{{ __('site.products.gallery') }}">
                                @foreach ($gallery as $index => $image)
                                    <li>
                                        <button type="button"
                                                @click="select({{ $index }})"
                                                :class="active === {{ $index }} ? 'border-olive-deep' : 'border-line hover:border-line-strong'"
                                                class="media-frame media-frame-1-1 block w-full border transition-colors">
                                            <img src="{{ $image->urlFor(200) }}"
                                                 alt="{{ $image->alt() }}"
                                                 loading="lazy" decoding="async">
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @else
                        <div class="flex aspect-square items-center justify-center border border-line bg-surface-sunken text-ink-subtle/40">
                            <svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path d="M3 6h18v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6ZM8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </div>
                    @endif

                    {{-- Full-screen view.

                         This is how a phone gets to the detail a mouse gets
                         from the magnifier: the browser's own pinch-to-zoom on
                         a full-resolution image, which is more capable and more
                         familiar than anything worth reimplementing. It is also
                         the keyboard route — the button above opens it and
                         Escape closes it.

                         `touch-action: pinch-zoom` is what permits the gesture
                         inside an element the page would otherwise treat as
                         scrollable. --}}
                    <div x-show="lightbox"
                         x-cloak
                         x-on:keydown.escape.window="closeLightbox()"
                         x-transition:enter="transition-opacity duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:leave="transition-opacity duration-150"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-[var(--z-modal)] flex items-center justify-center bg-charcoal/90 p-4"
                         role="dialog"
                         aria-modal="true"
                         :aria-label="current?.alt">

                        <div class="absolute inset-0" x-on:click="closeLightbox()" aria-hidden="true"></div>

                        <div class="relative flex size-full max-w-6xl flex-col" x-trap.noscroll="lightbox">
                            <div class="flex flex-none justify-end pb-3">
                                <button type="button"
                                        x-on:click="closeLightbox()"
                                        class="rounded-sm p-2 text-white/80 transition-colors hover:text-white
                                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-white"
                                        aria-label="{{ __('site.close') }}">
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" aria-hidden="true">
                                        <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-auto" style="touch-action: pinch-zoom;">
                                <template x-if="lightbox && current">
                                    <img :src="zoomSource"
                                         :alt="current.alt"
                                         class="mx-auto h-auto w-auto max-w-none"
                                         decoding="async">
                                </template>
                            </div>

                            <p class="flex-none pt-3 text-center text-xs text-white/60">
                                {{ __('site.products.zoom_hint') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div>
                    @if ($product->category)
                        <p class="eyebrow">{{ $product->category->t('name') }}</p>
                    @endif

                    <h1 class="mt-4 text-balance text-h1 font-bold text-ink">{{ $product->name() }}</h1>

                    @if ($product->t('short_description'))
                        <p class="mt-5 text-lead text-ink-muted">{{ $product->t('short_description') }}</p>
                    @endif

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('quote.create', ['product' => $product->id]) }}" class="btn btn-primary">
                            {{ __('site.cta.request_quote') }}
                        </a>
                        @if ($product->specSheet)
                            <a href="{{ route('products.spec-sheet', ['product' => $product->slug]) }}" class="btn btn-outline">
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.44L6.03 7.97a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                                {{ __('site.cta.download_spec') }}
                            </a>
                        @endif
                    </div>

                    @if ($siblings->isNotEmpty())
                        <div class="mt-8">
                            <h2 class="text-h3 text-ink">{{ __('catalog.product.other_forms') }}</h2>
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach ($siblings as $sib)
                                    <li><a class="btn btn-outline" href="{{ route('products.show', ['product' => $sib->slug]) }}">{{ $sib->form?->localized('name') ?: $sib->name() }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Specifications: empty optional fields never render --}}
                    @if ($specs !== [])
                        <div class="mt-10">
                            <h2 class="text-h3 text-ink">{{ __('site.products.specifications') }}</h2>
                            <dl class="mt-5 border-t border-line">
                                @foreach ($specs as $spec)
                                    <div class="spec-row">
                                        <dt class="spec-label">{{ $spec['label'] }}</dt>
                                        <dd class="spec-value break-anywhere">{{ $spec['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    @include('partials.nutrition')

                    @if ($product->certifications->isNotEmpty())
                        <div class="mt-10">
                            <h2 class="text-h3 text-ink">{{ __('site.products.certifications') }}</h2>
                            <ul class="mt-5 flex flex-wrap items-center gap-6">
                                @foreach ($product->certifications as $certification)
                                    <li>
                                        @if ($certification->logo)
                                            <img src="{{ $certification->logo->urlFor(120) }}"
                                                 alt="{{ $certification->localized('name') }}"
                                                 class="h-10 w-auto" loading="lazy" decoding="async">
                                        @else
                                            <span class="border border-line px-3 py-1.5 text-sm text-ink-muted">{{ $certification->localized('name') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Description: the product's own text; until it has one, the variety and form copy that describes this exact combination --}}
            @if ($product->t('description'))
                <div class="mt-16 max-w-3xl border-t border-line pt-12">
                    <div class="prose-wr">{!! $product->t('description') !!}</div>
                </div>
            @elseif ($product->variety?->localized('description') || $product->form?->localized('description'))
                <div class="mt-16 max-w-3xl border-t border-line pt-12">
                    <div class="prose-wr">
                        @if ($product->variety?->localized('description'))<p>{{ $product->variety->localized('description') }}</p>@endif
                        @if ($product->form?->localized('description'))<p>{{ $product->form->localized('description') }}</p>@endif
                    </div>
                </div>
            @endif

            {{-- Packaging options --}}
            @if ($product->packagingTypes->isNotEmpty())
                <div class="mt-16 border-t border-line pt-12">
                    <h2 class="text-h2 font-bold text-ink">{{ __('site.products.packaging_options') }}</h2>

                    <div class="mt-8 overflow-x-auto">
                        <table class="w-full min-w-[40rem] border-collapse text-[0.9375rem]">
                            <thead>
                                <tr class="border-b border-line-strong text-start">
                                    <th class="spec-label py-3 text-start">{{ __('admin.fields.packaging') }}</th>
                                    <th class="spec-label py-3 text-start">{{ __('site.packaging.capacity') }}</th>
                                    <th class="spec-label py-3 text-start">{{ __('site.packaging.drained_weight') }}</th>
                                    <th class="spec-label py-3 text-start">{{ __('site.packaging.net_weight') }}</th>
                                    <th class="spec-label py-3 text-start">{{ __('site.packaging.units_per_carton') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($product->packagingTypes as $packaging)
                                    @php($pivot = $packaging->pivot)
                                    <tr class="border-b border-line">
                                        <td class="py-3.5 font-medium text-ink">{{ $packaging->t('name') }}</td>
                                        <td class="py-3.5 text-ink-muted">{{ $packaging->capacityLabel() ?? '—' }}</td>
                                        <td class="py-3.5 text-ink-muted">{{ \App\Models\PackagingType::formatWeight($pivot->drained_weight_g ?? $packaging->drained_weight_g) ?? '—' }}</td>
                                        <td class="py-3.5 text-ink-muted">{{ \App\Models\PackagingType::formatWeight($pivot->net_weight_g ?? $packaging->net_weight_g) ?? '—' }}</td>
                                        <td class="py-3.5 text-ink-muted">{{ $pivot->units_per_carton ?? $packaging->units_per_carton ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Related --}}
            @if ($product->relatedProducts->isNotEmpty())
                <div class="mt-16 border-t border-line pt-12">
                    <h2 class="text-h2 font-bold text-ink">{{ __('site.products.related') }}</h2>
                    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($product->relatedProducts as $related)
                            <x-product-card :product="$related" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
