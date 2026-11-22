<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PackagingKind;
use App\Models\PackagingType;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PackagingStandardFiveTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<PackagingType> */
    private function fivePacks(): array
    {
        $out = [];
        foreach ([
            ['plastic-drum-225l', PackagingKind::Drum, '225 L Plastic Drum'],
            ['bucket-5kg-white', PackagingKind::PlasticPail, '5 kg White Plastic Bucket'],
            ['bucket-10kg-white', PackagingKind::PlasticPail, '10 kg White Plastic Bucket'],
            ['bucket-5kg-clear', PackagingKind::PlasticPail, '5 kg Clear Plastic Bucket'],
            ['bucket-10kg-clear', PackagingKind::PlasticPail, '10 kg Clear Plastic Bucket'],
        ] as [$slug, $kind, $name]) {
            $p = PackagingType::create(['slug' => $slug, 'kind' => $kind, 'is_active' => true, 'display_order' => 1]);
            $p->translations()->create(['locale' => 'en', 'name' => $name]);
            $out[] = $p;
        }

        return $out;
    }

    #[Test]
    public function it_reports_by_default_then_gives_every_product_exactly_the_five_and_can_be_undone(): void
    {
        $this->seed();
        Storage::fake('local');
        $five = $this->fivePacks();
        $old = PackagingType::query()->where('slug', 'glass-jar-370ml')->firstOrFail();
        $count = Product::query()->count();
        $this->assertGreaterThan(0, $count);
        $before = DB::table('product_packaging')->count();
        $this->assertGreaterThan(0, $before);

        $this->artisan('packaging:standard-five')->expectsOutputToContain('')->assertSuccessful();
        $this->assertSame($before, DB::table('product_packaging')->count(), 'report-only must not change anything');

        $this->artisan('packaging:standard-five', ['--apply' => true])->assertSuccessful();
        $this->assertSame($count * 5, DB::table('product_packaging')->count());
        foreach (Product::query()->get() as $p) {
            $this->assertEqualsCanonicalizing(array_map(fn ($t) => $t->id, $five), $p->packagingTypes()->pluck('packaging_types.id')->all());
        }
        $this->assertFalse(DB::table('product_packaging')->where('packaging_type_id', $old->id)->exists());
        $this->assertNotNull(PackagingType::query()->find($old->id), 'the other packs themselves are not deleted');

        $file = collect(Storage::disk('local')->files('backups'))->first();
        $this->assertNotNull($file);
        $this->artisan('packaging:standard-five', ['--restore' => $file])->assertSuccessful();
        $this->assertSame($before, DB::table('product_packaging')->count());
    }

    #[Test]
    public function it_refuses_when_one_of_the_five_cannot_be_found_uniquely(): void
    {
        $this->seed();
        $before = DB::table('product_packaging')->count();
        $this->artisan('packaging:standard-five', ['--apply' => true])->assertFailed();
        $this->assertSame($before, DB::table('product_packaging')->count());
    }
}
