<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A transparent overlay design drawn above a product photo. Only the overlay URL is stored: product originals are never touched. */
class ProductFrame extends Model
{
    public const BUILTIN_EMERALD_GOLD = 'frames/wadi-emerald-gold-frame.webp';

    protected $fillable = ['name', 'source', 'builtin_path', 'media_id', 'ratio', 'padding_pct', 'is_active', 'display_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'ratio' => 'float', 'padding_pct' => 'integer'];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    /** Public URL of the overlay artwork, or null when its file is gone. */
    public function url(): ?string
    {
        if ($this->source === 'builtin') {
            return $this->builtin_path && is_file(public_path($this->builtin_path)) ? asset($this->builtin_path).'?v='.filemtime(public_path($this->builtin_path)) : null;
        }

        return $this->media?->url();
    }

    /** A 480 px copy of a built-in overlay (`name-480.webp` beside it), for small cards; null when there is none. */
    public function smallUrl(): ?string
    {
        if ($this->source !== 'builtin' || ! $this->builtin_path) {
            return null;
        }
        $small = preg_replace('/\.webp$/', '-480.webp', $this->builtin_path);

        return $small !== null && $small !== $this->builtin_path && is_file(public_path($small)) ? asset($small).'?v='.filemtime(public_path($small)) : null;
    }

    /** Artwork ratio (width / height): stored, refreshed from the file when it is replaced. */
    public static function ratioOf(?MediaAsset $m): float
    {
        return $m && $m->width && $m->height ? round($m->width / $m->height, 4) : 1.0;
    }
}
