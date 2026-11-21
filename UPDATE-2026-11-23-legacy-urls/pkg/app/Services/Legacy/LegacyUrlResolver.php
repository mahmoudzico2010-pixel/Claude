<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Category;
use App\Models\NewsPost;
use App\Models\Product;
use App\Models\Variety;
use App\Services\Catalog\ProductPhotoMatcher;
use App\Support\Locales;
use App\Support\PublicHost;
use Illuminate\Http\Request;

/**
 * The last line of defence for every address Google (or anyone) still holds that no longer exists: it is asked ONLY when a page
 * would otherwise be a 404, and answers with a permanent redirect to the most relevant page that really exists — or 410 Gone for
 * addresses that never had a counterpart (feeds, shop carts, wishlist links, WordPress internals), so search engines drop them quickly.
 *
 * It is a set of GENERAL rules, not a list of products:
 *   1. normalise the address (case, encoding, query string, index.php, public/, .html, language prefix or language sub-domain);
 *   2. find the SECTION it belongs to from words in 12 languages (products, packaging, about, contact, quote, export, quality, news …);
 *   3. inside the products section, find the product by the WORDS of the address (cultivar, colour, cut, in any order and spelling),
 *      else the cultivar (variety) page, else the category page, else the products index — never a 404 for a shop address;
 *   4. an address in no known section is matched against product / category / variety names as a whole; otherwise it stays a 404.
 * A target is always built with route() on the preferred host: nothing from the request (host, path, query) is ever copied into the
 * Location header, so it cannot be used as an open redirect. Only published products and active categories/varieties are ever targets.
 */
final class LegacyUrlResolver
{
    /** Words (any language) that name a site section → the route that now serves it. */
    private const SECTIONS = [
        'products' => ['product', 'products', 'shop', 'store', 'catalog', 'catalogue', 'producto', 'productos', 'produit', 'produits', 'prodotto', 'prodotti', 'produto', 'produtos', 'tovary', 'produkty', 'produkte', 'منتجات', 'المنتجات', 'منتج', 'всі', 'товары', 'продукция', 'προϊόντα', 'προιοντα', '产品', 'پروڈکٹس', 'shop-all', 'product-category', 'product_cat', 'product-brand', 'brand', 'category', 'categories', 'productcategory', 'olives', 'pickles', 'peppers', 'oil'],
        'packaging' => ['packaging', 'packing', 'packages', 'package', 'pack', 'packs', 'embalaje', 'empaque', 'emballage', 'imballaggio', 'embalagem', 'verpackung', 'упаковка', '包装', 'التعبئة', 'تعبئة', 'عبوات'],
        'about' => ['about', 'about-us', 'aboutus', 'company', 'who-we-are', 'our-story', 'history', 'nosotros', 'quienes-somos', 'a-propos', 'qui-sommes-nous', 'chi-siamo', 'sobre-nos', 'sobre-nosotros', 'uber-uns', 'о-нас', '关于我们', 'من-نحن', 'عن-الشركة'],
        'contact' => ['contact', 'contact-us', 'contactus', 'contacto', 'contactenos', 'contatto', 'contatti', 'contato', 'contactez-nous', 'kontakt', 'контакты', '联系我们', 'اتصل-بنا', 'تواصل-معنا', 'get-in-touch'],
        'quote' => ['request-a-quote', 'request-quote', 'quote', 'quotation', 'rfq', 'inquiry', 'enquiry', 'get-a-quote', 'cotizacion', 'solicitar-presupuesto', 'devis', 'preventivo', 'orcamento', 'angebot', 'عرض-سعر', 'طلب-عرض-سعر'],
        'export' => ['export', 'exports', 'exportacion', 'exportation', 'esportazione', 'exportacao', 'export-markets', 'markets', 'member_cat', 'member-cat', 'shipping', 'logistics', 'تصدير'],
        'quality' => ['quality', 'certificates', 'certificate', 'certifications', 'certification', 'standards', 'haccp', 'calidad', 'qualite', 'qualita', 'qualidade', 'qualitat', 'качество', '质量', 'الجودة', 'جودة'],
        'private-label' => ['private-label', 'private-labelling', 'private-labeling', 'privatelabel', 'own-brand', 'oem', 'marca-blanca', 'marque-blanche', 'marchio-privato', 'marca-propria', 'eigenmarke', 'частная-марка', '自有品牌', 'علامة-خاصة'],
        'news' => ['news', 'blog', 'press', 'media', 'exhibitions', 'exhibition', 'events', 'noticias', 'actualites', 'notizie', 'noticias-y-eventos', 'nachrichten', 'новости', '新闻', 'اخبار', 'الأخبار'],
        'team' => ['team', 'our-team', 'members', 'management', 'equipo', 'equipe', 'squadra', 'mannschaft', 'команда', '团队', 'فريق-العمل', 'lp-profile'],
        'privacy' => ['privacy', 'privacy-policy', 'cookie-policy', 'cookies', 'datenschutz', 'politica-de-privacidad', 'confidentialite'],
        'terms' => ['terms', 'terms-and-conditions', 'terms-of-service', 'condiciones', 'conditions', 'agb'],
    ];

    /** Segments / query keys that have NO counterpart on the new site: answer 410 Gone. */
    private const GONE_SEGMENTS = ['feed', 'feeds', 'comments', 'trackback', 'oembed', 'xmlrpc', 'xmlrpc.php', 'wp-json', 'wp-admin', 'wp-login', 'wp-content', 'wp-includes', 'wp-cron', 'wp-cron.php', 'admin-ajax', 'cart', 'basket', 'checkout', 'my-account', 'wishlist', 'compare', 'login', 'register', 'lost-password', 'author', 'tag', 'udesign_template', 'udesign-template', 'xmlrpc', 'amp', 'embed', 'sitemap_index', 'sitemap_index.xml', 'trackbacks'];

    private const GONE_QUERY = ['add_to_wishlist', 'add-to-cart', 'wc-ajax', 'replytocom', 'remove_item', 'orderby', 'filter_', 'min_price', 'max_price', 'add_to_compare', 'removed_item'];

    /** Subdomain / TLD hints → language. */
    private const HOST_LANG = ['en' => 'en', 'ar' => 'ar', 'es' => 'es', 'sp' => 'es', 'fr' => 'fr', 'it' => 'it', 'pt' => 'pt', 'br' => 'pt', 'el' => 'el', 'gr' => 'el', 'ru' => 'ru', 'zh' => 'zh', 'cn' => 'zh', 'ur' => 'ur'];

    private const NOISE = ['public', 'index', 'php', 'html', 'htm', 'page', 'pages', 'all', 'item', 'items', 'p', 'view', 'details', 'detail', 'en', 'ar', 'es', 'fr', 'it', 'pt', 'el', 'ru', 'zh', 'ur'];

    private ProductPhotoMatcher $matcher;

    public function __construct()
    {
        $this->matcher = new ProductPhotoMatcher(publishedOnly: true);
    }

    /**
     * @return array{status: int, url: ?string, rule: string}|null  null = a genuine 404
     */
    public function resolve(Request $request): ?array
    {
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return null;
        }
        $locale = $this->localeHint($request) ?? Locales::default();
        $segments = $this->segments($request->path(), $locale);

        // 1. Addresses with no counterpart at all → 410.
        if ($this->isGone($segments, $request)) {
            return ['status' => 410, 'url' => null, 'rule' => 'gone'];
        }
        if ($segments === []) {
            return null;
        }

        // 2. Section by words.
        [$section, $subject] = $this->section($segments);
        if ($section !== null) {
            $url = $this->forSection($section, $subject, $locale);
            if ($url !== null && $this->samePath($url, $request) === false) {
                return ['status' => 301, 'url' => $url, 'rule' => 'section:'.$section];
            }
        }

        // 3. No section word: the whole address may still be a product / category / cultivar name.
        $url = $this->bySubject($segments, $locale, strictness: 'high');
        if ($url !== null && $this->samePath($url, $request) === false) {
            return ['status' => 301, 'url' => $url, 'rule' => 'name'];
        }

        return null;
    }

    // ------------------------------------------------------------------ parsing

    private function localeHint(Request $request): ?string
    {
        // a language sub-domain (it.example.com, sp.example.com) of the preferred host
        $pref = PublicHost::host();
        $host = strtolower($request->getHost());
        if ($pref !== null && $host !== $pref && str_ends_with($host, '.'.preg_replace('/^www\./', '', $pref))) {
            $label = explode('.', $host)[0];
            $l = self::HOST_LANG[$label] ?? null;
            if ($l !== null && Locales::isSupported($l)) {
                return $l;
            }
        }
        $first = strtolower((string) ($request->segments()[0] ?? ''));
        if ($first === 'public' && isset($request->segments()[1])) {
            $first = strtolower((string) $request->segments()[1]);
        }

        return Locales::isSupported($first) ? $first : null;
    }

    /** @return list<string> lower-case, decoded, de-noised path segments */
    private function segments(string $path, string $locale): array
    {
        $path = strtolower(rawurldecode($path));
        $path = preg_replace('/\.(html?|php|aspx?|jsp)$/', '', $path) ?? $path;
        $out = [];
        foreach (explode('/', trim($path, '/')) as $i => $s) {
            $s = trim($s);
            if ($s === '' || $s === 'index' || $s === 'index.php' || $s === 'public') {
                continue;
            }
            if ($i <= 1 && Locales::isSupported($s)) {
                continue;     // a language prefix: the language is already known from localeHint()
            }
            $out[] = $s;
        }

        return $out;
    }

    /** @param list<string> $segments */
    private function isGone(array $segments, Request $request): bool
    {
        foreach ($segments as $s) {
            if (in_array($s, self::GONE_SEGMENTS, true) || str_starts_with($s, 'wp-')) {
                return true;
            }
        }
        foreach (array_keys($request->query()) as $k) {
            $k = strtolower((string) $k);
            foreach (self::GONE_QUERY as $g) {
                if ($k === $g || str_starts_with($k, $g)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param list<string> $segments @return array{0: ?string, 1: list<string>} section key and the segments after it */
    private function section(array $segments): array
    {
        foreach ($segments as $i => $seg) {
            foreach (self::SECTIONS as $key => $words) {
                if (in_array($seg, $words, true)) {
                    // "olives", "pickles", "peppers", "oil" are product WORDS, not a section of their own: only a section when first
                    if (in_array($seg, ['olives', 'pickles', 'peppers', 'oil', 'brand', 'category', 'categories'], true) && $i > 0) {
                        continue;
                    }

                    return [$key, array_slice($segments, $i + (in_array($seg, ['olives', 'pickles', 'peppers', 'oil'], true) ? 0 : 1))];
                }
            }
        }

        return [null, $segments];
    }

    // ------------------------------------------------------------------ targets

    /** @param list<string> $subject */
    private function forSection(string $section, array $subject, string $locale): ?string
    {
        return match ($section) {
            'products' => $this->forProducts($subject, $locale),
            'packaging' => route('packaging.index', ['locale' => $locale]),
            'about' => route('about', ['locale' => $locale]),
            'contact' => route('contact.show', ['locale' => $locale]),
            'quote' => route('quote.create', ['locale' => $locale]),
            'export' => route('export', ['locale' => $locale]),
            'quality' => route('quality', ['locale' => $locale]),
            'private-label' => route('private-label', ['locale' => $locale]),
            'news' => $this->forNews($subject, $locale),
            'team' => route('team.index', ['locale' => $locale]),
            'privacy' => route('privacy', ['locale' => $locale]),
            'terms' => route('terms', ['locale' => $locale]),
            default => null,
        };
    }

    /** @param list<string> $subject */
    private function forNews(array $subject, string $locale): string
    {
        $slug = end($subject) ?: '';
        if ($slug !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1 && NewsPost::query()->published()->where('slug', $slug)->exists()) {
            return route('news.show', ['locale' => $locale, 'post' => $slug]);
        }

        return route('news.index', ['locale' => $locale]);
    }

    /** Inside the products section a result is ALWAYS found: product → cultivar → category → the products index. @param list<string> $subject */
    private function forProducts(array $subject, string $locale): string
    {
        return $this->bySubject($subject, $locale, strictness: 'low') ?? route('products.index', ['locale' => $locale]);
    }

    /**
     * @param  list<string>  $words  path segments (words joined by - or _)
     * @param  'high'|'low'  $strictness  high: only a clear product / exact name (used outside the products section)
     */
    private function bySubject(array $words, string $locale, string $strictness): ?string
    {
        $text = implode(' ', array_map(fn (string $s): string => str_replace(['-', '_', '+'], ' ', $s), $words));
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return null;
        }
        $matches = $this->matcher->match($text, 4);
        $top = $matches[0] ?? null;

        // a clear product
        if ($top !== null && $top['score'] >= 90 && ! $top['ambiguous']) {
            return route('products.show', ['locale' => $locale, 'product' => $top['slug']]);
        }
        if ($strictness === 'high') {
            // outside the products section only an EXACT name is trusted
            if ($top !== null && $top['score'] === 100) {
                return route('products.show', ['locale' => $locale, 'product' => $top['slug']]);
            }

            return $this->exactTaxon($words, $locale);
        }

        // one cultivar / category named by the words
        if (($v = $this->bestTaxon($text, Variety::query()->where("is_active", true)->whereHas('products', fn ($q) => $q->published())->get(['slug']), 'variety')) !== null) {
            return route('products.variety', ['locale' => $locale, 'variety' => $v]);
        }
        if (($c = $this->bestTaxon($text, Category::query()->active()->whereHas('products', fn ($q) => $q->published())->get(['slug']), 'category')) !== null) {
            return route('products.category', ['locale' => $locale, 'category' => $c]);
        }

        // several close products that share one category → that category page
        $cat = $this->commonCategory(array_filter($matches, fn (array $m): bool => $m['score'] >= 60));
        if ($cat !== null) {
            return route('products.category', ['locale' => $locale, 'category' => $cat]);
        }
        if ($top !== null && $top['score'] >= 75 && ! $top['ambiguous']) {
            return route('products.show', ['locale' => $locale, 'product' => $top['slug']]);
        }

        return null;
    }

    /** @param list<string> $words */
    private function exactTaxon(array $words, string $locale): ?string
    {
        $slug = end($words) ?: '';
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            return null;
        }
        foreach ([$slug, preg_replace('/-products$/', '', $slug)] as $s) {
            if (Category::query()->active()->where('slug', $s)->whereHas('products', fn ($q) => $q->published())->exists()) {
                return route('products.category', ['locale' => $locale, 'category' => $s]);
            }
            if (Variety::query()->where('is_active', true)->where('slug', $s)->whereHas('products', fn ($q) => $q->published())->exists()) {
                return route('products.variety', ['locale' => $locale, 'variety' => $s]);
            }
        }

        return null;
    }

    /**
     * The one record whose words are ALL present in the address (and which names more of it than any other record).
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $records
     */
    private function bestTaxon(string $text, $records, string $kind): ?string
    {
        $words = $this->words($text);
        $best = null;
        $bestN = 0;
        $tie = false;
        foreach ($records as $r) {
            $rw = $this->words((string) $r->slug);
            if ($rw === [] || array_diff($rw, $words) !== []) {
                continue;
            }
            $n = count($rw);
            if ($n > $bestN) {
                [$best, $bestN, $tie] = [(string) $r->slug, $n, false];
            } elseif ($n === $bestN) {
                $tie = true;
            }
        }

        return $best !== null && ! $tie ? $best : null;
    }

    /** @param array<int, array{slug: string}> $matches */
    private function commonCategory(array $matches): ?string
    {
        if (count($matches) < 2) {
            return null;
        }
        $slugs = Product::query()->published()->whereIn('slug', array_column($matches, 'slug'))->with('category')->get()->map(fn (Product $p) => $p->category?->slug)->unique()->values();

        return $slugs->count() === 1 && $slugs[0] !== null && Category::query()->active()->where('slug', $slugs[0])->whereHas('products', fn ($q) => $q->published())->exists() ? (string) $slugs[0] : null;
    }

    /** @return list<string> comparable words: folded plurals, de-noised */
    private function words(string $text): array
    {
        $t = strtolower((string) (function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT', $text) : $text));
        $out = [];
        foreach (preg_split('/[^a-z0-9]+/', $t) ?: [] as $w) {
            if ($w === '' || in_array($w, self::NOISE, true) || in_array($w, ['product', 'products', 'category', 'categories', 'shop'], true)) {
                continue;
            }
            if (strlen($w) > 4 && str_ends_with($w, 's') && ! str_ends_with($w, 'ss')) {
                $w = substr($w, 0, -1);
            }
            $out[$w] = true;
        }

        return array_keys($out);
    }

    private function samePath(string $url, Request $request): bool
    {
        return rtrim((string) parse_url($url, PHP_URL_PATH), '/') === rtrim('/'.$request->path(), '/');
    }
}
