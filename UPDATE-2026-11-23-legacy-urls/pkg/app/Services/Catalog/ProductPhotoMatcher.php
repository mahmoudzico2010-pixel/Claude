<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Product;

/**
 * Suggests which existing product a photo belongs to, from its FILE NAME only.
 *
 * It is a ranking aid, never a decision: nothing is assigned, created or changed. The file name is normalised (case, accents,
 * separators, extension, copy/number noise, plural and spelling variants such as halved/halves, jalape-os/jalapeno) and compared with
 * each product's slug, English name, cultivar, cut and colour. Each product gets a score from 0 to 100 and a confidence band:
 *
 *   high   ≥ 90  the name and the product say the same thing (exact slug = 100)
 *   medium ≥ 65  most of the name matches, or two products are almost equally good (ambiguous)
 *   low    ≥ 40  a weak overlap — worth a look, not worth trusting
 *
 * Files named after a camera or a counter (BEKO0402, IMG_2231, photo 3) carry no product information and get no suggestion.
 */
final class ProductPhotoMatcher
{
    /** Words that say nothing about the product. */
    private const NOISE = ['img', 'image', 'images', 'photo', 'photos', 'pic', 'picture', 'copy', 'final', 'new', 'old', 'edit', 'edited', 'web', 'webp', 'jpg', 'jpeg', 'png', 'v1', 'v2', 'v3', 'main', 'primary', 'gallery', 'product', 'products', 'and', 'the', 'of', 'in', 'a'];

    /** Spelling / grammar variants folded to one token. */
    private const SYNONYMS = [
        'halves' => 'half', 'halved' => 'half', 'halfs' => 'half',
        'sliced' => 'slice', 'slices' => 'slice', 'slicing' => 'slice', 'rings' => 'slice', 'ring' => 'slice',
        'pitted' => 'pit', 'stoned' => 'pit', 'pits' => 'pit', 'seedless' => 'pit', 'destoned' => 'pit',
        'stuffed' => 'stuff', 'filled' => 'stuff',
        'jalapenos' => 'jalapeno', 'jalapeno' => 'jalapeno', 'jalape' => 'jalapeno', 'os' => '', 'olives' => 'olive', 'peppers' => 'pepper', 'pickles' => 'pickle', 'pickled' => 'pickle', 'gherkins' => 'gherkin',
        'cherries' => 'cherry', 'onions' => 'onion', 'carrots' => 'carrot', 'cucumbers' => 'cucumber', 'cucumber' => 'cucumber',
        'oxidised' => 'oxidized', 'oxidized' => 'oxidized', 'colour' => 'color',
        'serscola' => 'sers', 'cola' => '',
    ];

    /** @var list<array{product: Product, slug: list<string>, name: list<string>, all: list<string>, hasPhoto: bool}>|null */
    private ?array $index = null;

    /** Only published products can be suggested (used when the answer is shown to the PUBLIC, e.g. redirecting an old address). */
    public function __construct(private readonly bool $publishedOnly = false) {}

    /**
     * @return list<array{product_id: int, slug: string, name: string, score: int, confidence: string, reasons: list<string>, has_photo: bool, ambiguous: bool}>
     */
    public function match(string $fileName, int $limit = 3): array
    {
        $file = $this->tokens(pathinfo($fileName, PATHINFO_FILENAME));
        if ($file === [] || $this->looksLikeCounter($fileName, $file)) {
            return [];
        }
        $squashed = $this->squash(pathinfo($fileName, PATHINFO_FILENAME));

        $scored = [];
        foreach ($this->index() as $row) {
            $score = 0.0;
            $why = [];
            $slugSquashed = $this->squash($row['product']->slug);

            if ($squashed !== '' && $squashed === $slugSquashed) {
                $score = 100.0;
                $why[] = 'exact slug';
            } else {
                $dSlug = $this->dice($file, $row['slug']);
                $dName = $this->dice($file, $row['name']);
                $dAll = $this->dice($file, $row['all']) * 0.92;
                $best = max($dSlug, $dName, $dAll);
                $score = $best * 100;
                $common = array_values(array_intersect($file, $row['all']));
                if ($common !== []) {
                    $why[] = 'shared words: '.implode(', ', $common);
                }
                // A file that contains every word of the slug (and little else) is as good as a spelling variant of it.
                if ($row['slug'] !== [] && array_diff($row['slug'], $file) === [] && count($file) <= count($row['slug']) + 1) {
                    $score = max($score, 92.0);
                    $why[] = 'contains the whole slug';
                }
                // The file's words are all there, but the product has more (e.g. the file omits the cut) → plausible, not certain.
                if ($file !== [] && array_diff($file, $row['all']) === [] && count($file) >= 2) {
                    $score = max($score, 55.0 + 10 * (count($file) / max(1, count($row['slug']))));
                    $why[] = 'every word of the file name is in the product';
                }
                // A word of the file that contradicts the product (a different cut/colour/cultivar) is a hard penalty.
                $score -= 12 * count($this->conflicts($file, $row));
            }
            if ($score < 40) {
                continue;
            }
            $scored[] = ['row' => $row, 'score' => min(100.0, max(0.0, $score)), 'why' => $why];
        }

        usort($scored, fn (array $a, array $b): int => [$b['score'], (int) ! $b['row']['hasPhoto']] <=> [$a['score'], (int) ! $a['row']['hasPhoto']]);

        $out = [];
        foreach (array_slice($scored, 0, max(1, $limit)) as $i => $s) {
            $score = (int) round($s['score']);
            $ambiguous = $i === 0 && isset($scored[1]) && ($s['score'] - $scored[1]['score']) < 5.0 && $s['score'] < 100.0;
            $confidence = $score >= 90 ? 'high' : ($score >= 65 ? 'medium' : 'low');
            if ($ambiguous && $confidence === 'high') {
                $confidence = 'medium';
            }
            $out[] = [
                'product_id' => (int) $s['row']['product']->getKey(), 'slug' => (string) $s['row']['product']->slug,
                'name' => (string) ($s['row']['product']->t('name', 'en') ?: $s['row']['product']->slug),
                'score' => $score, 'confidence' => $confidence, 'reasons' => $s['why'], 'has_photo' => $s['row']['hasPhoto'], 'ambiguous' => $ambiguous,
            ];
        }

        return $out;
    }

    /** @return list<array{product: Product, slug: list<string>, name: list<string>, all: list<string>, hasPhoto: bool}> */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }
        $this->index = [];
        foreach (Product::query()->when($this->publishedOnly, fn ($q) => $q->published())->with(['translations', 'variety', 'form'])->get() as $p) {
            $slug = $this->tokens((string) $p->slug);
            $name = $this->tokens((string) ($p->t('name', 'en') ?: ''));
            $extra = $this->tokens(trim(($p->variety?->name_en ?? '').' '.($p->form?->name_en ?? '').' '.($p->color ?? '')));
            $this->index[] = [
                'product' => $p, 'slug' => $slug, 'name' => $name,
                'all' => array_values(array_unique(array_merge($slug, $name, $extra))),
                'hasPhoto' => $p->primary_image_id !== null,
            ];
        }

        return $this->index;
    }

    /** Words (folded, de-noised) of a text. @return list<string> */
    private function tokens(string $text): array
    {
        $t = strtolower((string) (function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT', $text) : $text));
        $t = preg_replace('/[^a-z0-9]+/', ' ', $t) ?? '';
        $out = [];
        foreach (explode(' ', trim($t)) as $w) {
            if ($w === '' || ctype_digit($w) || in_array($w, self::NOISE, true) || preg_match('/^[a-z]{1,4}\d{3,}$/', $w) === 1) {
                continue;   // numbers and camera-style codes (BEKO0402, IMG2231) are not words
            }
            $w = self::SYNONYMS[$w] ?? $w;
            if ($w === '') {
                continue;
            }
            if (strlen($w) > 4 && str_ends_with($w, 's') && ! str_ends_with($w, 'ss')) {
                $w = substr($w, 0, -1);
            }
            $out[$w] = true;
        }

        return array_keys($out);
    }

    private function squash(string $s): string
    {
        $t = strtolower((string) (function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT', $s) : $s));

        return preg_replace('/[^a-z0-9]+/', '', $t) ?? '';
    }

    /** Sørensen–Dice of two word sets, 0..1. @param list<string> $a @param list<string> $b */
    private function dice(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }

        return 2 * count(array_intersect($a, $b)) / (count($a) + count($b));
    }

    /** Words in the file that name a DIFFERENT cut / colour than the product has. @param list<string> $file @param array{slug: list<string>, name: list<string>, all: list<string>} $row @return list<string> */
    private function conflicts(array $file, array $row): array
    {
        $groups = [['half', 'slice', 'pit', 'stuff', 'whole'], ['red', 'green', 'black', 'yellow', 'orange']];
        $bad = [];
        foreach ($groups as $g) {
            $inFile = array_values(array_intersect($file, $g));
            $inProduct = array_values(array_intersect($row['all'], $g));
            if ($inFile !== [] && $inProduct !== [] && array_intersect($inFile, $inProduct) === []) {
                $bad = array_merge($bad, $inFile);
            }
        }

        return $bad;
    }

    /** @param list<string> $tokens */
    private function looksLikeCounter(string $fileName, array $tokens): bool
    {
        return $tokens === [] || preg_match('/^(img|dsc|pxl|photo|image|beko|bk)[\s_\-]*\d+$/i', pathinfo($fileName, PATHINFO_FILENAME)) === 1;
    }
}
