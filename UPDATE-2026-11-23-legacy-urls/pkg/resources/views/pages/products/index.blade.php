@extends('layouts.public')

@section('content')
    <x-page-hero
        :eyebrow="__('site.nav.products')"
        :title="__('site.products.title')"
        :breadcrumbs="$seo->breadcrumbs"
    />

    @if (($families ?? collect())->isNotEmpty() && array_filter($filters) === [])
        <section class="section pb-0" aria-labelledby="families-h">
            <div class="container-wr">
                <h2 id="families-h" class="text-h2 text-ink">{{ __('catalogue.families') }}</h2>
                <p class="mt-2 max-w-2xl text-ink-muted">{{ __('catalogue.index_intro') }}</p>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($families as $family)
                        @include('pages.products.partials.category-card', ['cat' => $family])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="container-wr">
            <div class="lg:grid lg:grid-cols-12 lg:gap-12">

                {{-- Filters --}}
                <aside class="lg:col-span-3" x-data="{ open: false }" data-wr-filters>
                    <span hidden data-wrm-filter-strings data-label="{{ __('mobile.filters') }}" data-active="{{ __('mobile.filters_active') }}" data-title="{{ __('site.products.filter_by') }}" data-close="{{ __('site.close') }}" data-clear="{{ __('site.products.clear_filters') }}"></span>
                    <div class="flex items-center justify-between lg:hidden" data-wr-filter-toggle>
                        <h2 class="text-h3 text-ink">{{ __('site.products.filter_by') }}</h2>
                        <button type="button" @click="open = !open" class="btn btn-outline px-4 py-2 text-sm">
                            <span x-text="open ? '{{ __('site.close') }}' : '{{ __('admin.actions.filter') }}'">{{ __('admin.actions.filter') }}</span>
                        </button>
                    </div>

                    {{-- Rendered once. The panel is collapsed on small screens
                         and always open from `lg` up, decided in CSS rather
                         than by an Alpine width check — the previous version
                         read window.innerWidth a single time, so the panel
                         stayed wrong after a rotate or a resize.

                         Hidden by class rather than x-show, so a desktop
                         visitor sees the filters even if the JavaScript bundle
                         has not run yet. --}}
                    <div class="hidden lg:block" :class="{ '!block': open }" data-wr-filter-panel>
                        @include('partials.product-filters')
                    </div>
                </aside>

                {{-- Results --}}
                <div class="mt-10 lg:col-span-9 lg:mt-0">
                    @if ($products->total() > 0)
                        <p class="text-[0.875rem] text-ink-subtle">
                            {{ __('site.products.showing', ['count' => $products->count(), 'total' => $products->total()]) }}
                        </p>

                        <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>

                        <div class="mt-12">
                            {{ $products->links() }}
                        </div>
                    @else
                        <div class="border border-line bg-surface-alt px-6 py-16 text-center">
                            <h2 class="text-h3 text-ink">{{ __('site.products.no_results') }}</h2>
                            <p class="mx-auto mt-3 max-w-md text-[0.9375rem] text-ink-muted">
                                {{ __('site.products.no_results_hint') }}
                            </p>
                            <div class="mt-7 flex flex-wrap justify-center gap-3">
                                <a href="{{ route('products.index') }}" class="btn btn-outline">{{ __('site.products.clear_filters') }}</a>
                                <a href="{{ route('quote.create') }}" class="btn btn-primary">{{ __('site.cta.request_quote') }}</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
