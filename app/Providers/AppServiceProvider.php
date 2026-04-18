<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'sales_order'      => \App\Models\SalesOrder::class,
            'purchase_order'   => \App\Models\PurchaseOrder::class,
            'invoice'          => \App\Models\Invoice::class,
            'quotation'        => \App\Models\Quotation::class,
            'goods_receipt'    => \App\Models\GoodsReceipt::class,
        ]);

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin')) {
                return true;
            }
        });

        // Format any date/string as DD-MM-YYYY
        Blade::directive('formatDate', function ($expression) {
            return "<?php echo $expression ? \Carbon\Carbon::parse($expression)->format('d-m-Y') : '-'; ?>";
        });

        // Format date with time as DD-MM-YYYY HH:MM
        Blade::directive('formatDateTime', function ($expression) {
            return "<?php echo $expression ? \Carbon\Carbon::parse($expression)->format('d-m-Y H:i') : '-'; ?>";
        });
    }
}
