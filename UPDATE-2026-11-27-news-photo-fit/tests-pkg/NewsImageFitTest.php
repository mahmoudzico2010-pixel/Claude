<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\NewsType;
use App\Models\MediaAsset;
use App\Models\NewsPost;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** News / exhibition photos are always shown whole (fitted), never cropped: page hero, list cards, home section, gallery thumbnails. */
final class NewsImageFitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_news_pages_fit_the_whole_photo_instead_of_cropping_it(): void
    {
        $this->seed();
        SiteSetting::setMany(['maintenance_mode' => false], 'site');
        $m = MediaAsset::create(['disk' => 'public', 'path' => 'media/news/medal.webp', 'filename' => 'medal.webp', 'original_filename' => 'medal.webp', 'mime_type' => 'image/webp', 'extension' => 'webp', 'size' => 100, 'width' => 800, 'height' => 800, 'folder' => 'news', 'checksum' => sha1('medal')]);
        $p = NewsPost::create(['slug' => 'medal', 'type' => NewsType::News, 'status' => ContentStatus::Published, 'published_at' => now()->subDay(), 'featured_image_id' => $m->id]);
        $p->translations()->create(['locale' => 'en', 'title' => 'Medal']);

        $show = $this->get('/en/news/medal')->assertOk()->getContent();
        $this->assertStringContainsString('class="size-full object-contain"', $show);
        $this->assertStringNotContainsString('aspect-16/9 w-full object-cover', $show);

        $list = $this->get('/en/news')->assertOk()->getContent();
        $this->assertStringContainsString('object-contain', $list);
        $this->assertStringNotContainsString('size-full object-cover', $list);
    }
}
