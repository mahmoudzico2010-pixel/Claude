@php($posts = app(\App\Services\Builder\ElementData::class)->news($settings))

@if ($posts->isNotEmpty())
    <div data-builder-node="{{ $node['id'] }}" class="wr-node wr-news grid gap-6">
        @foreach ($posts as $post)
            <article class="card card-hover flex h-full flex-col overflow-hidden">
                <a href="{{ route('news.show', ['post' => $post->slug]) }}" class="block">
                    @if ($post->featuredImage)
                        <div class="media-frame media-frame-16-9 bg-surface-sunken">
                            <img src="{{ $post->featuredImage->urlFor(640) }}" alt=""
                                 class="size-full object-contain" loading="lazy" decoding="async">
                        </div>
                    @endif
                    <div class="p-6">
                        @if ($post->published_at)
                            <time datetime="{{ $post->published_at->toDateString() }}" class="text-xs uppercase tracking-wide text-ink-muted">
                                {{ $post->published_at->translatedFormat('j F Y') }}
                            </time>
                        @endif
                        <h3 class="mt-2 text-h4 text-ink">{{ $post->t('title') }}</h3>
                        @if ($post->t('excerpt'))
                            <p class="mt-2 line-clamp-3 text-[0.9375rem] text-ink-muted">{{ $post->t('excerpt') }}</p>
                        @endif
                    </div>
                </a>
            </article>
        @endforeach
    </div>
@endif
