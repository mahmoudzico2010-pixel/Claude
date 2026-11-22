{{-- Photo slideshow + video gallery of a news item / exhibition. Static JS (public/js/wr-news-gallery.js), native <dialog> lightbox: no framework, no build step.
     Without JavaScript the slides are a swipeable, scroll-snapping strip; the script upgrades it to a cross-fading, auto-advancing slideshow. --}}
@php
    $photos = $post->galleryPhotos->filter(fn ($i) => $i->media && $i->media->isImage())->values();
    $videos = $post->galleryVideos->filter(fn ($i) => $i->media && $i->media->isVideo());
    $n = $photos->count();
@endphp
@if ($photos->isNotEmpty() || $videos->isNotEmpty())
    <section class="container-wr mt-14 wr-ng" aria-labelledby="wr-ng-title" data-wr-ng>
        <style>
            /* ---- slideshow ---- */
            .wr-ss{position:relative}
            .wr-ss-stage{position:relative;overflow:hidden;background:#0e2e27;aspect-ratio:16/9}
            @media (max-width:639px){.wr-ss-stage{aspect-ratio:4/3}}
            .wr-ss-track{display:flex;margin:0;padding:0;list-style:none;height:100%;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none}
            .wr-ss-track::-webkit-scrollbar{display:none}
            .wr-ss-slide{flex:0 0 100%;height:100%;scroll-snap-align:center;position:relative}
            /* JS on: slides stack and cross-fade */
            .wr-ss.is-js .wr-ss-track{display:block;overflow:hidden;position:relative}
            .wr-ss.is-js .wr-ss-slide{position:absolute;inset:0;opacity:0;visibility:hidden;transition:opacity .9s ease,visibility 0s linear .9s}
            .wr-ss.is-js .wr-ss-slide.is-active{opacity:1;visibility:visible;transition:opacity .9s ease,visibility 0s}
            /* The whole photo is shown (contain) over a blurred, enlarged copy of itself: no cropping of heads or text. */
            .wr-ss-open{display:block;position:relative;width:100%;height:100%;border:0;padding:0;background:transparent;cursor:zoom-in;overflow:hidden;isolation:isolate}
            .wr-ng-bg{position:absolute;inset:-12%;z-index:-1;background-size:cover;background-position:center;filter:blur(22px) saturate(1.1);transform:scale(1.1);opacity:.9}
            .wr-ss-open img{width:100%;height:100%;object-fit:contain;display:block}
            .wr-ss.is-js .wr-ss-slide.is-active .wr-ss-open img{animation:wr-ss-drift 7s ease-out both}
            @keyframes wr-ss-drift{from{transform:scale(1)}to{transform:scale(1.045)}}
            .wr-ss-open:focus-visible{outline:3px solid #c6a15b;outline-offset:-3px}
            .wr-ss-expand{position:absolute;bottom:.75rem;inset-inline-end:.75rem;display:flex;align-items:center;gap:.4rem;background:rgb(255 255 255 / .92);color:#173f35;font-size:.8125rem;font-weight:600;padding:.4rem .7rem;pointer-events:none;opacity:.0;transform:translateY(6px);transition:opacity .25s ease,transform .25s ease}
            .wr-ss-stage:hover .wr-ss-expand,.wr-ss-open:focus-visible .wr-ss-expand{opacity:1;transform:none}
            @media (hover:none){.wr-ss-expand{opacity:1;transform:none}}
            /* progress bar of the current slide */
            .wr-ss-progress{position:absolute;inset-inline:0;bottom:0;height:3px;background:rgb(255 255 255 / .25);z-index:3;display:none}
            .wr-ss.is-js.is-playing .wr-ss-progress{display:block}
            .wr-ss-progress i{display:block;height:100%;width:100%;background:#c6a15b;transform-origin:left center;transform:scaleX(0)}
            [dir=rtl] .wr-ss-progress i{transform-origin:right center}
            .wr-ss.is-playing .wr-ss-progress i{animation:wr-ss-bar var(--wr-ss-ms,5000ms) linear both}
            @keyframes wr-ss-bar{to{transform:scaleX(1)}}
            /* controls */
            .wr-ss-btn{position:absolute;z-index:4;display:none;align-items:center;justify-content:center;min-width:2.75rem;min-height:2.75rem;border:0;background:rgb(255 255 255 / .88);color:#173f35;cursor:pointer;font-size:1.4rem;line-height:1}
            .wr-ss.is-js .wr-ss-btn{display:flex}
            .wr-ss-btn:hover{background:#fff}.wr-ss-btn:focus-visible{outline:3px solid #c6a15b;outline-offset:2px}
            .wr-ss-prev{top:50%;inset-inline-start:.75rem;transform:translateY(-50%)}
            .wr-ss-next{top:50%;inset-inline-end:.75rem;transform:translateY(-50%)}
            [dir=rtl] .wr-ss-prev span,[dir=rtl] .wr-ss-next span{display:inline-block;transform:scaleX(-1)}
            .wr-ss-play{top:.75rem;inset-inline-end:.75rem;font-size:1rem}
            .wr-ss-count{position:absolute;z-index:4;top:.75rem;inset-inline-start:.75rem;background:rgb(14 46 39 / .72);color:#fff;font-size:.8125rem;padding:.25rem .6rem;display:none}
            .wr-ss.is-js .wr-ss-count{display:block}
            /* thumbnail strip */
            .wr-ss-thumbs{display:none;gap:.5rem;margin:.75rem 0 0;padding:0 0 .25rem;list-style:none;overflow-x:auto;scrollbar-width:thin}
            .wr-ss.is-js .wr-ss-thumbs{display:flex}
            .wr-ss-thumbs button{display:block;position:relative;flex:none;width:5.5rem;aspect-ratio:4/3;border:0;padding:0;cursor:pointer;background:#e9e6da;overflow:hidden;opacity:.6;transition:opacity .25s ease,outline-color .25s ease;outline:3px solid transparent;outline-offset:-3px}
            .wr-ss-thumbs button img{width:100%;height:100%;object-fit:contain;display:block}
            .wr-ss-thumbs button:hover{opacity:.9}
            .wr-ss-thumbs button[aria-current="true"]{opacity:1;outline-color:#c6a15b}
            .wr-ss-thumbs button:focus-visible{outline-color:#173f35;opacity:1}
            @media (min-width:768px){.wr-ss-thumbs button{width:7rem}}
            /* ---- videos: ONE LINE, like the photo slideshow (swipe / arrows); every tile has the same height and its own width, so portrait
                   videos are tall-narrow and landscape ones wide; the first scene is shown as the thumbnail ---- */
            .wr-vs{position:relative;margin-top:1.5rem}
            .wr-vs-track{display:flex;gap:.75rem;margin:0;padding:0 0 .5rem;list-style:none;overflow-x:auto;scroll-snap-type:x proximity;scrollbar-width:thin;-webkit-overflow-scrolling:touch;overscroll-behavior-x:contain}
            .wr-vs-item{flex:0 0 auto;scroll-snap-align:start;margin:0;height:var(--wr-vh,16rem);aspect-ratio:var(--wr-ar,16/9);max-width:calc(100vw - 2rem);background:#0e2e27;position:relative;overflow:hidden}
            @media (min-width:768px){.wr-vs-item{--wr-vh:22rem}}
            .wr-vs-item video{width:100%;height:100%;object-fit:contain;display:block;background:#0e2e27}
            .wr-vs-item.is-portrait{--wr-ar:9/16}
            .wr-vs-btn{position:absolute;top:calc(50% - .25rem);z-index:3;display:none;align-items:center;justify-content:center;min-width:2.75rem;min-height:2.75rem;border:0;background:rgb(255 255 255 / .9);color:#173f35;cursor:pointer;font-size:1.4rem;line-height:1;transform:translateY(-50%)}
            .wr-vs.is-js.has-overflow .wr-vs-btn{display:flex}
            .wr-vs-btn:hover{background:#fff}.wr-vs-btn:focus-visible{outline:3px solid #c6a15b;outline-offset:2px}
            .wr-vs-prev{inset-inline-start:.5rem}.wr-vs-next{inset-inline-end:.5rem}
            [dir=rtl] .wr-vs-btn span{display:inline-block;transform:scaleX(-1)}
            .wr-vs-count{margin:.25rem 0 0;font-size:.8125rem;color:#5a635c}
            .wr-ng-vid{margin:0}
            .wr-ng-verr{margin:0;font-size:.8125rem;color:#7a4b00;background:#fbf3df;padding:.4rem .6rem;position:absolute;inset-inline:0;bottom:0;z-index:2}
            .wr-ng-verr a{font-weight:600;text-decoration:underline;color:#173f35}
            /* ---- lightbox (manual browsing) ---- */
            dialog.wr-ng-box{border:0;padding:0;background:transparent;max-width:100vw;max-height:100dvh;width:100vw;height:100dvh}
            dialog.wr-ng-box::backdrop{background:rgb(8 22 18 / .94)}
            .wr-ng-stage{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:3.5rem 3.5rem 4rem}
            .wr-ng-stage img{max-width:100%;max-height:100%;object-fit:contain;transition:opacity .25s ease,transform .3s ease}
            .wr-ng-stage img.is-swap{opacity:0;transform:translateX(var(--wr-ng-dx,24px))}
            dialog.wr-ng-box[open]{animation:wr-ng-in .28s ease both}
            dialog.wr-ng-box[open]::backdrop{animation:wr-ng-fade .28s ease both}
            @keyframes wr-ng-in{from{opacity:0;transform:scale(.98)}to{opacity:1;transform:none}}
            @keyframes wr-ng-fade{from{opacity:0}to{opacity:1}}
            .wr-ng-btn{position:fixed;z-index:2;min-width:2.75rem;min-height:2.75rem;border:0;background:rgb(255 255 255 / .14);color:#fff;font-size:1.5rem;line-height:1;cursor:pointer;border-radius:2px}
            .wr-ng-btn:hover,.wr-ng-btn:focus-visible{background:rgb(255 255 255 / .3);outline:2px solid #c6a15b}
            .wr-ng-close{top:.75rem;inset-inline-end:.75rem}
            .wr-ng-prev{top:50%;inset-inline-start:.75rem;transform:translateY(-50%)}
            .wr-ng-next{top:50%;inset-inline-end:.75rem;transform:translateY(-50%)}
            .wr-ng-count{position:fixed;bottom:1rem;inset-inline:0;text-align:center;color:#fff;font-size:.875rem}
            [dir=rtl] .wr-ng-prev span,[dir=rtl] .wr-ng-next span{display:inline-block;transform:scaleX(-1)}
            @media (prefers-reduced-motion:reduce){
                .wr-ss.is-js .wr-ss-slide,.wr-ss.is-js .wr-ss-slide.is-active{transition:none}
                .wr-ss.is-js .wr-ss-slide.is-active .wr-ss-open img{animation:none}
                .wr-ss-progress i{animation:none!important}
                .wr-ss-expand,.wr-ss-thumbs button,.wr-ng-stage img{transition:none!important}
                dialog.wr-ng-box[open],dialog.wr-ng-box[open]::backdrop{animation:none}
            }
        </style>

        @if ($n > 0)
            <h2 id="wr-ng-title" class="text-h3 font-bold text-ink">{{ __('newsgallery.photos') }}</h2>
            <div class="wr-ss mt-6" data-wr-ss data-interval="5000" role="region" aria-roledescription="carousel" aria-label="{{ __('newsgallery.photos') }}"
                 data-l-pause="{{ __('newsgallery.pause') }}" data-l-play="{{ __('newsgallery.play') }}">
                <div class="wr-ss-stage">
                    <ul class="wr-ss-track" role="list" data-wr-ss-track>
                        @foreach ($photos as $i => $item)
                            @php($m = $item->media)
                            <li class="wr-ss-slide{{ $loop->first ? ' is-active' : '' }}" role="group" aria-roledescription="slide" aria-label="{{ sprintf(__('newsgallery.photo_n'), $loop->iteration, $n) }}">
                                <button type="button" class="wr-ss-open" data-wr-ng-open="{{ $loop->index }}" data-full="{{ $m->urlFor(1600) }}" data-alt="{{ $m->alt() ?: $post->title() }}"
                                        aria-label="{{ __('newsgallery.open') }}: {{ sprintf(__('newsgallery.photo_n'), $loop->iteration, $n) }}">
                                    <span class="wr-ng-bg" aria-hidden="true" style="background-image:url('{{ $m->urlFor(400) }}')"></span>
                                    <img src="{{ $m->urlFor(1280) }}" @if ($m->srcset()) srcset="{{ $m->srcset() }}" sizes="(min-width:1280px) 76rem, 100vw" @endif
                                         alt="{{ $m->alt() ?: $post->title() }}" width="{{ $m->width ?: 1280 }}" height="{{ $m->height ?: 720 }}"
                                         @if ($loop->first) fetchpriority="low" @else loading="lazy" @endif decoding="async">
                                    <span class="wr-ss-expand" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>{{ __('newsgallery.open') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    @if ($n > 1)
                        <button type="button" class="wr-ss-btn wr-ss-prev" data-wr-ss-prev aria-label="{{ __('newsgallery.prev') }}"><span aria-hidden="true">&#8249;</span></button>
                        <button type="button" class="wr-ss-btn wr-ss-next" data-wr-ss-next aria-label="{{ __('newsgallery.next') }}"><span aria-hidden="true">&#8250;</span></button>
                        <button type="button" class="wr-ss-btn wr-ss-play" data-wr-ss-play aria-label="{{ __('newsgallery.pause') }}" aria-pressed="true"><span aria-hidden="true" data-wr-ss-playicon>&#10074;&#10074;</span></button>
                    @endif
                    <p class="wr-ss-count" dir="ltr" data-wr-ss-count aria-live="off">1 / {{ $n }}</p>
                    <div class="wr-ss-progress" aria-hidden="true"><i data-wr-ss-bar></i></div>
                </div>
                @if ($n > 1)
                    <ul class="wr-ss-thumbs" role="list" aria-label="{{ __('newsgallery.photos') }}">
                        @foreach ($photos as $i => $item)
                            <li><button type="button" data-wr-ss-go="{{ $loop->index }}" @if ($loop->first) aria-current="true" @endif aria-label="{{ sprintf(__('newsgallery.photo_n'), $loop->iteration, $n) }}">
                                <img src="{{ $item->media->urlFor(400) }}" alt="" width="160" height="120" loading="lazy" decoding="async"></button></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <dialog class="wr-ng-box" data-wr-ng-dialog aria-label="{{ __('newsgallery.photos') }}" data-label-n="{{ __('newsgallery.photo_n') }}">
                <div class="wr-ng-stage"><img alt="" data-wr-ng-img></div>
                <button type="button" class="wr-ng-btn wr-ng-close" data-wr-ng-close aria-label="{{ __('newsgallery.close') }}">&times;</button>
                <button type="button" class="wr-ng-btn wr-ng-prev" data-wr-ng-prev aria-label="{{ __('newsgallery.prev') }}"><span aria-hidden="true">&#8249;</span></button>
                <button type="button" class="wr-ng-btn wr-ng-next" data-wr-ng-next aria-label="{{ __('newsgallery.next') }}"><span aria-hidden="true">&#8250;</span></button>
                <p class="wr-ng-count" data-wr-ng-count aria-live="polite"></p>
            </dialog>
        @endif

        @if ($videos->isNotEmpty())
            <h2 @if ($n === 0) id="wr-ng-title" @endif class="text-h3 font-bold text-ink {{ $n > 0 ? 'mt-12' : '' }}">{{ __('newsgallery.videos') }}</h2>
            <div class="wr-vs" data-wr-vs role="region" aria-roledescription="carousel" aria-label="{{ __('newsgallery.videos') }}">
                <ul class="wr-vs-track" role="list" data-wr-vs-track>
                    @foreach ($videos as $item)
                        @php($sz = $item->media->videoSize())
                        @php($ar = $sz ? $sz[0].'/'.$sz[1] : null)
                        <li class="wr-vs-item wr-ng-vid {{ $sz ? ($sz[1] > $sz[0] ? 'is-portrait' : ($sz[1] === $sz[0] ? 'is-square' : 'is-landscape')) : '' }}" data-wr-vid
                            @if ($ar) style="--wr-ar:{{ $ar }}" @endif data-wr-known="{{ $sz ? '1' : '0' }}">
                            <video controls preload="metadata" playsinline aria-label="{{ $item->media->alt() ?: $post->title() }}">
                                {{-- #t=0.001 makes iPhone Safari draw the FIRST SCENE as the thumbnail instead of a dark box --}}
                                <source src="{{ $item->media->url() }}#t=0.001" type="{{ $item->media->playbackType() }}">
                            </video>
                            <p class="wr-ng-verr" data-wr-verr hidden role="status">{{ __('newsgallery.video_error') }}
                                <a href="{{ $item->media->url() }}" target="_blank" rel="noopener">{{ __('newsgallery.open_video') }}</a></p>
                        </li>
                    @endforeach
                </ul>
                @if ($videos->count() > 1)
                    <button type="button" class="wr-vs-btn wr-vs-prev" data-wr-vs-prev aria-label="{{ __('newsgallery.prev') }}"><span aria-hidden="true">&#8249;</span></button>
                    <button type="button" class="wr-vs-btn wr-vs-next" data-wr-vs-next aria-label="{{ __('newsgallery.next') }}"><span aria-hidden="true">&#8250;</span></button>
                @endif
            </div>
        @endif
    </section>
    @if ($n > 0 || $videos->isNotEmpty())
        @push('scripts')<script src="{{ asset('js/wr-news-gallery.js') }}?v={{ @filemtime(public_path('js/wr-news-gallery.js')) }}" defer></script>@endpush
    @endif
@endif
