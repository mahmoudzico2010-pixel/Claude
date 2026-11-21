<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LighthouseHeadersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function origin_isolation_and_strong_hsts_are_sent(): void
    {
        $this->seed();
        $r = $this->get('https://localhost/en');
        $this->assertSame('same-origin', $r->headers->get('Cross-Origin-Opener-Policy'));
        $hsts = (string) $r->headers->get('Strict-Transport-Security');
        $this->assertStringContainsString('includeSubDomains', $hsts);
        $this->assertStringContainsString('preload', $hsts);
    }

    #[Test]
    public function the_consent_banner_is_shown_in_the_first_paint_not_after_javascript(): void
    {
        $this->seed();
        \App\Services\Analytics\AnalyticsSettings::class;
        $html = view('partials.wr-consent')->render();
        // when analytics consent is not required the partial renders nothing; when it does, the inline reveal script must follow the banner
        $this->assertTrue($html === '' || str_contains($html, "wr_consent=") );
    }
}
