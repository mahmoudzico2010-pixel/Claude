@extends('layouts.public')

@section('content')
    <x-page-hero :eyebrow="__('site.nav.news')" :title="__('site.news.title')" :breadcrumbs="$seo->breadcrumbs" />

    <section class="section">
        <div class="container-wr">
            {{-- Type filter --}}
            <div class="flex flex-wrap gap-2 border-b border-line pb-6">
                <a href="{{ route('news.index') }}"
                   @class([
                       'px-4 py-2 text-sm font-medium transition-colors',
                       'bg-olive-deep text-white' => empty($filters['type']),
                       'border border-line text-ink-muted hover:border-olive-deep hover:text-olive-deep' => ! empty($filters['type']),
                   ])>{{ __('site.news.all_types') }}</a>

                @foreach ($types as $type)
                    <a href="{{ route('news.index', ['type' => $type->value]) }}"
                       @class([
                           'px-4 py-2 text-sm font-medium transition-colors',
                           'bg-olive-deep text-white' => ($filters['type'] ?? null) === $type->value,
                           'border border-line text-ink-muted hover:border-olive-deep hover:text-olive-deep' => ($filters['type'] ?? null) !== $type->value,
                       ])>{{ $type->getLabel() }}</a>
                @endforeach
            </div>

            @if ($posts->total() > 0)
                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <article class="card card-hover group flex h-full flex-col overflow-hidden">
                            @if ($post->featuredImage)
                                <a href="{{ route('news.show', ['post' => $post->slug]) }}" class="block aspect-16/10 overflow-hidden bg-surface-sunken">
                                    <img src="{{ $post->featuredImage->urlFor(640) }}"
                                         alt="{{ $post->featuredImage->alt() }}"
                                         class="size-full object-contain transition-transform duration-500 group-hover:scale-[1.03]"
                                         loading="lazy" decoding="async">
                                </a>
                            @endif

                            <div class="flex flex-1 flex-col p-6">
                                <p class="text-[0.75rem] font-semibold uppercase tracking-wider text-olive-muted">
                                    {{ $post->type->getLabel() }}
                                    @if ($post->dateLabel())<span class="text-ink-subtle"> · {{ $post->dateLabel() }}</span>@endif
                                </p>

                                <h2 class="mt-3 text-h3">
                                    <a href="{{ route('news.show', ['post' => $post->slug]) }}" class="text-ink transition-colors hover:text-olive-deep">
                                        {{ $post->title() }}
                                    </a>
                                </h2>

                                @if ($post->t('excerpt'))
                                    <p class="mt-2 line-clamp-3 text-[0.9375rem] leading-relaxed text-ink-muted">{{ $post->t('excerpt') }}</p>
                                @endif

                                @if ($post->location)
                                    <p class="mt-4 flex items-center gap-2 text-[0.8125rem] text-ink-subtle">
                                        <svg class="size-3.5 flex-none text-gold" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.69 18.933A1 1 0 0 0 10 19a1 1 0 0 0 .31-.067c2.71-1.13 7.19-4.9 7.19-10.183a7.5 7.5 0 0 0-15 0c0 5.283 4.48 9.053 7.19 10.183ZM10 11.25a2.75 2.75 0 1 0 0-5.5 2.75 2.75 0 0 0 0 5.5Z" clip-rule="evenodd"/></svg>
                                        {{ $post->location }}
                                    </p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-12">{{ $posts->links() }}</div>
            @else
                <div class="mt-10 border border-line bg-surface-alt px-6 py-16 text-center">
                    <p class="text-ink-muted">{{ __('site.news.no_results') }}</p>
                </div>
            @endif
        </div>
    </section>
@endsection
