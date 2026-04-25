<?php

namespace App\Console\Commands;

use App\Services\Asset\DepreciationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PostAssetDepreciation extends Command
{
    protected $signature = 'assets:depreciate {--as-of= : Month-end date (Y-m-d). Defaults to today.} {--dry-run : Show preview only}';

    protected $description = 'Post monthly depreciation for all eligible assets (idempotent).';

    public function handle(DepreciationService $service): int
    {
        $asOf = $this->option('as-of') ? Carbon::parse($this->option('as-of')) : Carbon::now();

        if ($this->option('dry-run')) {
            $rows = $service->preview($asOf);
            $this->info("DRY-RUN: {$rows->count()} assets would be depreciated. Total: ₹".number_format($rows->sum('monthly'), 2));
            $this->table(['Asset', 'Method', 'Monthly', 'New Book Value'],
                $rows->map(fn ($r) => [$r->asset_code.' '.$r->name, $r->method, $r->monthly, $r->after_book_value])->take(20)->toArray());
            return self::SUCCESS;
        }

        $result = $service->postMonth($asOf);
        $this->info("Posted depreciation for {$result['posted_count']} assets totalling ₹".number_format($result['total_amount'], 2)." (as of {$result['as_of']}).");

        return self::SUCCESS;
    }
}
