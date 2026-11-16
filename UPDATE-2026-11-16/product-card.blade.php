@props(['product', 'frame' => null])

@php
    $image = $product->displayImage();
    // Frame overlay: per product (inherit / chosen / none), resolved at render time. `frame` may be passed to preview a design (false = none).
    $frameData = $image ? ($frame === null ? \App\Support\ProductFrames::for($product) : ($frame ?: null)) : null;
@endphp

<article class="card card-hover group flex h-full flex-col overflow-hidden">
    <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="block">
        {{-- The shared Stage 1 media frame, not bespoke styling.

             This used to be `object-cover`, which crops to fill: a tall jar in
             a 4:3 box lost its lid and its base. `contain` fits the whole
             product inside the area instead, and the frame keeps every card
             the same height whatever the source image's proportions are.

             `media-frame-zoom` scales the image on hover without touching the
             card — see the note in media-frame.css for why it is scoped to
             devices with a real pointer. --}}
        <div class="media-frame {{ $frameData ? '' : 'media-frame-4-3' }} media-frame-zoom {{ $frameData ? 'bg-white' : 'bg-surface-sunken' }}" @if ($frameData) style="--media-frame-ratio: {{ $frameData['ratio'] }}" @endif>
            @if ($image && $frameData)
                {{-- Framed: the whole product sits inside the inner padding; the transparent artwork is drawn above it. --}}
                <img src="{{ $image->urlFor(640) }}" @if ($image->srcset()) srcset="{{ $image->srcset() }}" sizes="(min-width: 1024px) 24rem, (min-width: 640px) 45vw, 90vw" @endif
                     alt="{{ $image->alt() }}" loading="lazy" decoding="async"
                     style="object-fit: contain; padding: {{ $frameData['padding'] }}%; box-sizing: border-box">
                <img src="{{ $frameData['url'] }}" alt="" aria-hidden="true" loading="lazy" decoding="async" class="wr-frame-overlay"
                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:fill;pointer-events:none;z-index:2;padding:0">
            @elseif ($image)
                <img src="{{ $image->urlFor(640) }}"
                     @if ($image->srcset()) srcset="{{ $image->srcset() }}" sizes="(min-width: 1024px) 24rem, (min-width: 640px) 45vw, 90vw" @endif
                     alt="{{ $image->alt() }}"
                     loading="lazy" decoding="async"
                     width="{{ $image->width }}" height="{{ $image->height }}">
            @else
                {{-- A designed fallback, not an empty rectangle.

                     A product photograph is sometimes genuinely missing, and
                     the previous grey box with a faint outline read as a
                     broken image. This is a deliberate brand panel carrying
                     the product's own initial: it looks intended, keeps the
                     card the same height as its neighbours, and never invents
                     a jar or a label the company does not sell.

                     An administrator can replace it globally by setting a
                     fallback image under Homepage → Featured products. --}}
                @php($fallback = home_setting_media('home.product_fallback_image'))

                @if ($fallback)
                    <img src="{{ $fallback->urlFor(640) }}"
                         alt="{{ $product->name() }}"
                         loading="lazy" decoding="async">
                @else
                    <span class="flex size-full flex-col items-center justify-center gap-2
                                 bg-gradient-to-br from-surface-alt to-surface-sunken">
                        <span class="flex size-14 items-center justify-center rounded-full bg-olive-deep/10
                                     text-h3 font-bold text-olive-deep/70">
                            {{ mb_strtoupper(mb_substr($product->name() ?? '?', 0, 1)) }}
                        </span>
                        <span class="text-[0.6875rem] uppercase tracking-wider text-ink-subtle">
                            {{ $product->category?->t('name') }}
                        </span>
                    </span>
                @endif
            @endif

            @if ($product->is_featured)
                <span class="absolute top-3 start-3 bg-gold px-2.5 py-1 text-[0.6875rem] font-semibold uppercase tracking-wider text-forest">
                    {{ __('admin.fields.is_featured') }}
                </span>
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col p-5">
        @if ($product->category)
            <p class="text-[0.75rem] font-semibold uppercase tracking-wider text-olive-muted">
                {{ $product->category->t('name') }}
            </p>
        @endif

        <h3 class="mt-2 text-h3">
            <a href="{{ route('products.show', ['product' => $product->slug]) }}"
               class="text-ink transition-colors hover:text-olive-deep">
                {{ $product->name() }}
            </a>
        </h3>

        @if ($product->t('short_description'))
            <p class="mt-2 line-clamp-2 text-[0.9375rem] leading-relaxed text-ink-muted">
                {{ $product->t('short_description') }}
            </p>
        @endif

        <div class="mt-auto pt-5">
            <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="link-rule">
                {{ __('site.read_more') }}
                <svg class="rtl-flip size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </div>
</article>
