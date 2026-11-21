<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Old / indexed addresses resolve generally (301 to the best current page, 410 for junk) — no per-product entries. */
final class LegacyUrlResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @return array<string, array{string, string}> */
    public static function redirects(): array
    {
        return [
            'screenshot url (category + cultivar)' => ['/public/products/natural-black-olives/sers-cola', '/en/products/'],
            'cultivar with published products' => ['/product-category/olives/manzanilla', '/en/products/'],
            'index.php wrapper' => ['/index.php/product/green-olives-sliced/', '/en/products/green-olives-sliced'],
            'wishlist query only on a real product -> junk' => ['/product/cherry-peppers/?add_to_wishlist=6864', ''],
            'category with -products suffix' => ['/product-category/pickled-products-products/', '/en/products/category/pickled-products'],
            'spanish shop' => ['/productos/', '/en/products'],
            'export legacy section' => ['/member_cat/export/', '/en/export'],
            'contact in italian' => ['/contatto', '/en/contact'],
            'language prefix + html' => ['/en/about-us.html', '/en/about'],
            'unknown product in shop -> products index' => ['/shop/zzz-unknown-thing/', '/en/products'],
        ];
    }

    #[Test]
    #[DataProvider('redirects')]
    public function old_addresses_redirect_permanently(string $from, string $to): void
    {
        $r = $this->get($from);
        if ($to === '') {
            $r->assertStatus(410);

            return;
        }
        $this->assertSame(301, $r->getStatusCode(), $from.' -> '.$r->getStatusCode());
        $path = parse_url((string) $r->headers->get('Location'), PHP_URL_PATH) ?: '';
        str_ends_with($to, '/') ? $this->assertStringStartsWith($to, $path.'/', $from) : $this->assertSame($to, $path, $from);
    }

    #[Test]
    public function junk_addresses_are_gone_not_404(): void
    {
        foreach (['/shop/feed/', '/feed/', '/wp-login.php', '/xmlrpc.php', '/cart/', '/my-account/', '/product/cherry-peppers/feed/'] as $u) {
            $this->get($u)->assertStatus(410);
        }
    }

    #[Test]
    public function the_query_string_is_never_copied_and_no_external_target_is_possible(): void
    {
        $r = $this->get('/productos/?next=https://evil.example/&utm=1');
        $loc = (string) $r->headers->get('Location');
        $this->assertStringNotContainsString('evil.example', $loc);
        $this->assertStringNotContainsString('utm', $loc);
        $r = $this->get('/shop/https:/evil.example');
        $this->assertStringNotContainsString('evil.example', (string) $r->headers->get('Location'));
    }

    #[Test]
    public function addresses_with_no_meaning_stay_404_and_post_is_untouched(): void
    {
        $this->get('/qwerty-nothing-here')->assertNotFound();
        $this->post('/shop/anything')->assertStatus(405);
    }

    #[Test]
    public function a_current_page_is_never_redirected_to_itself(): void
    {
        $this->get('/en/products')->assertOk();
    }
}
