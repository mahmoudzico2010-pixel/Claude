<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PackagingType;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Gives EVERY product exactly the five standard packs (225 L plastic drum; 5 kg and 10 kg white plastic bucket; 5 kg and 10 kg clear
 * plastic bucket) and nothing else. The five packs are found by their English names in the existing packaging list — nothing is created,
 * edited or deleted there; only the product↔pack links change. The old links are saved to a JSON file first so the change can be undone
 * (`--restore=<file>`). Without --apply it only reports what it would do.
 */
class PackagingStandardFiveCommand extends Command
{
    protected $signature = 'packaging:standard-five {--apply : really change the links (default: report only)} {--restore= : restore the links from a backup file made by --apply}';

    protected $description = 'Link every product to exactly the five standard packs (report-only unless --apply); reversible';

    /** @var array<string, array{label: string, test: callable(string): bool}> */
    private function targets(): array
    {
        $has = fn (string $n, string $re): bool => preg_match($re, $n) === 1;
        $pack = fn (string $n): bool => $has($n, '/\b(bucket|pail)\b/');

        return [
            'drum' => ['label' => '225 L Plastic Drum', 'test' => fn (string $n): bool => $has($n, '/\b225\s*l\b/') && $has($n, '/\bdrum\b/') && $has($n, '/\bplastic\b/')],
            'w5' => ['label' => '5 kg White Plastic Bucket', 'test' => fn (string $n): bool => $pack($n) && $has($n, '/\b5\s*kg\b/') && $has($n, '/\bwhite\b/')],
            'w10' => ['label' => '10 kg White Plastic Bucket', 'test' => fn (string $n): bool => $pack($n) && $has($n, '/\b10\s*kg\b/') && $has($n, '/\bwhite\b/')],
            'c5' => ['label' => '5 kg Clear Plastic Bucket', 'test' => fn (string $n): bool => $pack($n) && $has($n, '/\b5\s*kg\b/') && $has($n, '/\b(clear|transparent)\b/')],
            'c10' => ['label' => '10 kg Clear Plastic Bucket', 'test' => fn (string $n): bool => $pack($n) && $has($n, '/\b10\s*kg\b/') && $has($n, '/\b(clear|transparent)\b/')],
        ];
    }

    public function handle(): int
    {
        if ($file = (string) $this->option('restore')) {
            return $this->restore($file);
        }

        $types = PackagingType::query()->with('translations')->get();
        $chosen = [];
        $ok = true;
        foreach ($this->targets() as $key => $t) {
            $hits = $types->filter(fn (PackagingType $p): bool => ($t['test'])(mb_strtolower((string) ($p->t('name', 'en') ?: $p->slug))))->values();
            if ($hits->count() !== 1) {
                $ok = false;
                $this->error(sprintf('"%s": %d match(es)%s', $t['label'], $hits->count(), $hits->isEmpty() ? '' : ' → '.$hits->map(fn ($p) => $p->slug)->implode(', ')));

                continue;
            }
            $chosen[$key] = $hits->first();
            $this->line(sprintf('  %-28s = #%d %s', $t['label'], $hits->first()->id, $hits->first()->slug));
        }
        if (! $ok) {
            $this->error('Nothing changed: each of the five packs must match exactly one entry of the packaging list (check their English names).');

            return self::FAILURE;
        }

        $products = Product::query()->withTrashed()->get(['id']);
        $this->line($products->count().' product(s) will get exactly these 5 packs.');
        if (! $this->option('apply')) {
            $this->warn('Report only. Run again with --apply to make the change (a backup file is written first).');

            return self::SUCCESS;
        }

        $backup = DB::table('product_packaging')->get()->map(fn ($r) => (array) $r)->all();
        $name = 'backups/product-packaging-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($name, json_encode($backup, JSON_PRETTY_PRINT));
        $this->info('Backup of the old links: storage/app/private/'.$name.' (or storage/app/'.$name.')');

        DB::transaction(function () use ($products, $chosen): void {
            DB::table('product_packaging')->delete();
            $rows = [];
            foreach ($products as $p) {
                $i = 0;
                foreach ($chosen as $type) {
                    $rows[] = ['product_id' => $p->id, 'packaging_type_id' => $type->id, 'display_order' => ++$i];
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('product_packaging')->insert($chunk);
            }
        });
        $this->info('Done: '.$products->count().' product(s) now have exactly the 5 standard packs.');

        return self::SUCCESS;
    }

    private function restore(string $file): int
    {
        $disk = Storage::disk('local');
        $path = $disk->exists($file) ? $file : (str_starts_with($file, 'backups/') ? $file : 'backups/'.basename($file));
        if (! $disk->exists($path)) {
            $this->error('Backup file not found: '.$file);

            return self::FAILURE;
        }
        $rows = json_decode((string) $disk->get($path), true);
        if (! is_array($rows)) {
            $this->error('The backup file is not valid.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($rows): void {
            DB::table('product_packaging')->delete();
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('product_packaging')->insert($chunk);
            }
        });
        $this->info('Restored '.count($rows).' link(s).');

        return self::SUCCESS;
    }
}
