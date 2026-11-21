<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use App\Models\ProductFrame;

/**
 * Which frame (if any) a product photo gets. Per product: inherit the site setting, use a chosen frame, or explicitly none.
 * The decision is made at render time from settings, so changing it takes effect on refresh and no image is regenerated.
 */
final class ProductFrames
{
    /** @var array<int, ProductFrame|null> */
    private static array $memo = [];

    public static function forget(): void
    {
        self::$memo = [];
    }

    private static function frame(?int $id): ?ProductFrame
    {
        if ($id === null) {
            return null;
        }

        return self::$memo[$id] ??= ProductFrame::query()->with('media')->where('is_active', true)->find($id);
    }

    public static function siteEnabled(): bool
    {
        return filter_var(setting('media.frame_enabled', false), FILTER_VALIDATE_BOOLEAN) && self::frame((int) setting('media.frame_id') ?: null) !== null;
    }

    /** @return array{url: string, small?: ?string, ratio: float, padding: int, fit: string, name: string, id: int}|null */
    public static function for(?Product $product): ?array
    {
        $mode = $product?->frame_mode ?: 'inherit';
        if ($mode === 'none') {
            return null;
        }
        $frame = match (true) {
            $mode === 'frame' && $product?->frame_id !== null => self::frame((int) $product->frame_id),
            $mode === 'frame' => null,
            default => filter_var(setting('media.frame_enabled', false), FILTER_VALIDATE_BOOLEAN) ? self::frame((int) setting('media.frame_id') ?: null) : null,
        };

        return self::describe($frame);
    }

    /** @return array{url: string, small?: ?string, ratio: float, padding: int, fit: string, name: string, id: int}|null */
    public static function describe(?ProductFrame $frame, ?int $padding = null, ?string $fit = null): ?array
    {
        $url = $frame?->url();
        if ($frame === null || $url === null) {
            return null;
        }
        $global = setting('media.frame_padding');

        $fitMode = ($fit ?? setting('media.frame_fit')) === 'contain' ? 'contain' : 'cover';
        $pad = max(0, min(30, $padding ?? (filled($global) ? (int) $global : (int) $frame->padding_pct)));

        return ['url' => $url, 'small' => $frame->smallUrl(), 'ratio' => max(0.3, (float) $frame->ratio), 'fit' => $fitMode, 'padding' => $fitMode === 'cover' ? 0 : $pad, 'name' => $frame->name, 'id' => (int) $frame->id];
    }
}
