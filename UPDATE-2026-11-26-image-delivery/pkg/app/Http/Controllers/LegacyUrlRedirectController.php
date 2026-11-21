<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Variety;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 301 from the OLD website's addresses (still shown by Google) to the same page on the new site.
 *
 * Evidence for the patterns (owner's own screenshots): /contact-us, /cookie-policy, /public/product/<slug>, and Google listing a
 * product under "Shop > Green Olives" with the same product slug the new catalogue uses. The usual shop layouts (/product/<slug>,
 * /shop/<category>/<slug>, /product-category/<slug>) are handled the same way. A redirect happens ONLY when the target really
 * exists (a published product, an active category/variety, a public page); anything else is a plain 404 — never the homepage,
 * and never a redirect to a host or path taken from the request, so it cannot be used as an open redirect.
 */
class LegacyUrlRedirectController extends Controller
{
    public function __invoke(Request $request, ?string $path = null): \Symfony\Component\HttpFoundation\Response
    {
        // 1. The general resolver: junk (feeds, wishlist, cart …) is 410 Gone, whatever else the address looks like.
        $general = app(\App\Services\Legacy\LegacyUrlResolver::class)->resolve($request);
        if ($general !== null && $general['status'] === 410) {
            return response()->view('errors.410', [], 410)->header('X-Robots-Tag', 'noindex');
        }

        // 2. The explicit mappings (config/legacy.php) and exact category / product rules below stay authoritative; they only
        //    answer with the bare products index when they found nothing specific — then the general resolver may do better.
        $target = $this->resolve($this->clean((string) $request->path()));
        if ($target !== null && $target !== route('products.index')) {
            return redirect()->to($target, 301);
        }
        // A product that exists but is not published points to the section (never to a guessed neighbour).
        $last = strtolower(basename(rtrim((string) preg_replace('/\.(html?|php)$/', '', $request->path()), '/')));
        $knownButHidden = $last !== '' && \App\Models\Product::query()->where('slug', $last)->exists();
        if ($general !== null && ! $knownButHidden) {
            return redirect()->to((string) $general['url'], 301);
        }

        abort_if($target === null, 404);

        return redirect()->to($target, 301);
    }

    private bool $hadPublicPrefix = false;

    private function clean(string $path): string
    {
        $path = strtolower(trim(rawurldecode($path), '/'));
        $this->hadPublicPrefix = str_starts_with($path, 'public/');
        $path = preg_replace('#^public/#', '', $path) ?? $path;

        return preg_replace('/\.(html?|php)$/', '', $path) ?? $path;
    }

    private function resolve(string $path): ?string
    {
        $locale = Locales::default();
        $segments = $path === '' ? [] : explode('/', $path);
        $first = $segments[0] ?? '';
        // /public/en/products/x (the application folder showing in the address) -> /en/products/x
        if (Locales::isSupported($first) && count($segments) > 1 && $this->hadPublicPrefix) {
            return '/'.$path;
        }
        $last = end($segments) ?: '';
        $slug = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $last) === 1 ? $last : null;

        $fixed = [
            'contact-us' => fn () => route('contact.show', ['locale' => $locale]),
            'contact' => fn () => route('contact.show', ['locale' => $locale]),
            'about-us' => fn () => route('about', ['locale' => $locale]),
            'shop' => fn () => route('products.index', ['locale' => $locale]),
            'products' => fn () => route('products.index', ['locale' => $locale]),
            'privacy-policy' => fn () => $this->legal('privacy', $locale),
            'cookie-policy' => fn () => $this->legal('privacy', $locale),   // the privacy policy is where cookies are described
            'terms' => fn () => $this->legal('terms', $locale),
        ];
        if (count($segments) === 1 && isset($fixed[$first])) {
            return $fixed[$first]();
        }

        if ($slug === null) {
            return null;
        }

        if (in_array($first, ['product', 'shop', 'products'], true) && count($segments) >= 2) {
            foreach ($this->candidates($slug, 'products') as $c) {
                if (Product::query()->published()->where('slug', $c)->exists()) {
                    return route('products.show', ['locale' => $locale, 'product' => $c]);
                }
            }
            foreach ($this->candidates($slug, 'categories') as $c) {
                if (Category::query()->active()->where('slug', $c)->exists()) {
                    return route('products.category', ['locale' => $locale, 'category' => $c]);
                }
                if (Variety::query()->active()->where('slug', $c)->exists()) {
                    return route('products.variety', ['locale' => $locale, 'variety' => $c]);
                }
            }
            if (($m = $this->fuzzy($slug, Category::query()->active()->pluck('slug'))) !== null) {
                return route('products.category', ['locale' => $locale, 'category' => $m]);
            }
            if (($m = $this->fuzzy($slug, Product::query()->published()->pluck('slug'))) !== null) {
                return route('products.show', ['locale' => $locale, 'product' => $m]);
            }

            // An old shop address we cannot match to one page goes to the products section (relevant), never the homepage.
            return route('products.index', ['locale' => $locale]);
        }

        if (in_array($first, ['product-category', 'category', 'product_cat'], true) && count($segments) >= 2) {
            foreach ($this->candidates($slug, 'categories') as $c) {
                if (Category::query()->active()->where('slug', $c)->exists()) {
                    return route('products.category', ['locale' => $locale, 'category' => $c]);
                }
            }
            if (($m = $this->fuzzy($slug, Category::query()->active()->pluck('slug'))) !== null) {
                return route('products.category', ['locale' => $locale, 'category' => $m]);
            }

            return route('products.index', ['locale' => $locale]);
        }

        return null;
    }

    /** Words that only describe the old shop's layout, not the thing itself. */
    private const NOISE = ['product', 'products', 'category', 'categories', 'shop', 'all', 'item', 'items'];

    /** @return list<string> */
    private function tokens(string $slug): array
    {
        $t = array_values(array_diff(array_filter(explode('-', $slug)), self::NOISE));
        sort($t);

        return $t;
    }

    /**
     * Match an old name to ONE new record by its words, ignoring order and shop noise ("green-olives-products" ~ "olives-green").
     * Exact word-set match first; otherwise the old words must all appear in exactly one record. Two candidates = no answer.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $slugs
     */
    private function fuzzy(string $slug, $slugs): ?string
    {
        $want = $this->tokens($slug);
        if ($want === []) {
            return null;
        }
        $exact = $slugs->filter(fn ($s) => $this->tokens((string) $s) === $want)->values();
        if ($exact->count() === 1) {
            return (string) $exact[0];
        }
        if ($exact->count() > 1) {
            return null;
        }
        $subset = $slugs->filter(fn ($s) => array_diff($want, $this->tokens((string) $s)) === [])->values();

        return $subset->count() === 1 ? (string) $subset[0] : null;
    }

    /** The slug itself, then the owner's explicit mapping, then the slug without a known old-shop suffix. @return list<string> */
    private function candidates(string $slug, string $kind): array
    {
        $out = [$slug];
        $mapped = config('legacy.'.$kind.'.'.$slug);
        if (is_string($mapped) && $mapped !== '') {
            $out[] = $mapped;
        }
        foreach ((array) config('legacy.strip_suffixes', []) as $suffix) {
            if (is_string($suffix) && $suffix !== '' && str_ends_with($slug, $suffix) && strlen($slug) > strlen($suffix)) {
                $out[] = substr($slug, 0, -strlen($suffix));
            }
        }

        return array_values(array_unique(array_filter($out, fn ($c) => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $c) === 1)));
    }

    private function legal(string $key, string $locale): ?string
    {
        $public = Page::query()->published()->where('key', $key)->exists() && Page::publiclyLinkableLegalPages()->contains(fn ($p) => ($p['key'] ?? null) === $key);

        return $public ? route($key === 'terms' ? 'terms' : 'privacy', ['locale' => $locale]) : null;
    }
}
