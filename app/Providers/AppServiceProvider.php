<?php

namespace App\Providers;

use App\Auth\TenantAwareUserProvider;
use App\Listeners\MarkNotificationLogSent;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentBusiness::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('tenant-aware-eloquent', function ($app, array $config) {
            return new TenantAwareUserProvider($app['hash'], $config['model']);
        });

        Relation::morphMap([
            'sales_order'       => \App\Models\SalesOrder::class,
            'purchase_order'    => \App\Models\PurchaseOrder::class,
            'invoice'           => \App\Models\Invoice::class,
            'quotation'         => \App\Models\Quotation::class,
            'proforma_invoice'  => \App\Models\ProformaInvoice::class,
            'goods_receipt'     => \App\Models\GoodsReceipt::class,
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

        // Notification log: mark rows 'sent' once mail is delivered.
        Event::listen(MessageSending::class, [MarkNotificationLogSent::class, 'handleSending']);
        Event::listen(MessageSent::class, [MarkNotificationLogSent::class, 'handleSent']);
    }
}
