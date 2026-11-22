    {{-- ===================== News and exhibitions ================== --}}
    @if ($latestNews->isNotEmpty())
        <section class="reveal-ready section bg-surface-alt"
        x-data="reveal"
        x-bind:class="{ 'reveal-ready': ! shown, 'reveal-in': shown }">
            <div class="container-wr">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <x-section-heading :eyebrow="__('site.news.latest')" :title="__('site.news.title')" />
                    <a href="{{ route('news.index') }}" class="link-rule">
                        {{ __('site.view_all') }}
                        <svg class="rtl-flip size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                    </a>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ($latestNews as $post)
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
                                    @if ($post->dateLabel())
                                        <span class="text-ink-subtle"> · {{ $post->dateLabel() }}</span>
                                    @endif
                                </p>
                                <h3 class="mt-3 text-h3">
                                    <a href="{{ route('news.show', ['post' => $post->slug]) }}" class="text-ink transition-colors hover:text-olive-deep">
                                        {{ $post->title() }}
                                    </a>
                                </h3>
                                @if ($post->t('excerpt'))
                                    <p class="mt-2 line-clamp-3 text-[0.9375rem] leading-relaxed text-ink-muted">{{ $post->t('excerpt') }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
