@extends('layouts.public')

@section('content')
    <div class="border-b border-line bg-surface-alt">
        <div class="container-wr py-5">
            <x-breadcrumbs :items="$seo->breadcrumbs" />
        </div>
    </div>

    <article class="py-12 lg:py-16">
        <div class="container-narrow">
            <p class="eyebrow">
                {{ $post->type->getLabel() }}
                @if ($post->dateLabel())<span class="text-ink-subtle"> · {{ $post->dateLabel() }}</span>@endif
            </p>

            <h1 class="mt-5 text-balance text-h1 font-bold text-ink">{{ $post->title() }}</h1>

            @if ($post->t('excerpt'))
                <p class="mt-5 text-lead text-ink-muted">{{ $post->t('excerpt') }}</p>
            @endif

            @if ($post->location || $post->event_start_date)
                <dl class="mt-7 flex flex-wrap gap-x-10 gap-y-3 border-y border-line py-4">
                    @if ($post->event_start_date)
                        <div>
                            <dt class="spec-label">{{ $post->hasPeriod() ? __('newsgallery.period.label') : __('newsgallery.period.date') }}</dt>
                            <dd class="mt-0.5 text-[0.9375rem] text-ink">{{ $post->dateLabel() }}</dd>
                        </div>
                    @endif
                    @if ($post->location)
                        <div>
                            <dt class="spec-label">{{ __('site.news.location') }}</dt>
                            <dd class="mt-0.5 text-[0.9375rem] text-ink">{{ $post->location }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
        </div>

        @if ($post->featuredImage)
            <div class="container-wr mt-10">
                {{-- The WHOLE photograph is always shown (never cropped): it is fitted inside the area on a soft background. --}}
                <div class="aspect-16/9 w-full overflow-hidden bg-surface-sunken">
                    <img src="{{ $post->featuredImage->urlFor(1280) }}"
                         @if ($post->featuredImage->srcset()) srcset="{{ $post->featuredImage->srcset() }}" sizes="(min-width: 1280px) 76rem, 100vw" @endif
                         alt="{{ $post->featuredImage->alt() }}"
                         class="size-full object-contain"
                         fetchpriority="high" decoding="async">
                </div>
            </div>
        @endif

        @if ($post->t('body'))
            <div class="container-narrow mt-12">
                <div class="prose-wr">{!! $post->t('body') !!}</div>
            </div>
        @endif

        @include('pages.news._gallery')

        @if ($post->tags->isNotEmpty())
            <div class="container-narrow mt-10">
                <ul class="flex flex-wrap gap-2 border-t border-line pt-6">
                    @foreach ($post->tags as $tag)
                        <li class="border border-line px-3 py-1 text-[0.8125rem] text-ink-muted">{{ $tag->localized('name') }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </article>

    @if ($related->isNotEmpty())
        <section class="section bg-surface-alt">
            <div class="container-wr">
                <x-section-heading :title="__('site.news.latest')" />
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $item)
                        <article class="card card-hover flex h-full flex-col p-6">
                            <p class="text-[0.75rem] font-semibold uppercase tracking-wider text-olive-muted">{{ $item->type->getLabel() }}</p>
                            <h3 class="mt-3 text-h3">
                                <a href="{{ route('news.show', ['post' => $item->slug]) }}" class="text-ink transition-colors hover:text-olive-deep">{{ $item->title() }}</a>
                            </h3>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
