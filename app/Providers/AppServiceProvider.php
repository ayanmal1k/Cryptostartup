<?php

namespace App\Providers;

use App\Model\Attachment;
use App\Model\PaymentRequest;
use App\Model\Post;
use App\Model\Stream;
use App\Model\Subscription;
use App\Model\Transaction;
use App\Model\UserList;
use App\Model\UserMessage;
use App\Model\UserVerify;
use App\Model\Withdrawal;
use App\Observers\AttachmentsObserver;
use App\Observers\EngagementObserver;
use App\Observers\PaymentRequestsObserver;
use App\Observers\PostApprovalObserver;
use App\Observers\StreamsObserver;
use App\Observers\SubscriptionsObserver;
use App\Observers\TransactionsObserver;
use App\Observers\UserMessagesObserver;
use App\Observers\UsersObserver;
use App\Observers\UserVerifyObserver;
use App\Observers\WithdrawalsObserver;
use App\Model\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * TODO: Delete this once on L10.
     * @return void
     */
    public function register()
    {
        //
        // code in `register` method
        Event::listen(MigrationsStarted::class, function () {
            if (env('ALLOW_DISABLED_PK')) {
                DB::statement('SET SESSION sql_require_primary_key=0');
            }
        });

        Event::listen(MigrationsEnded::class, function () {
            if (env('ALLOW_DISABLED_PK')) {
                DB::statement('SET SESSION sql_require_primary_key=1');
            }
        });

        $this->app->singleton('cryptocurrency', function ($app) {
            return new \App\Services\CryptocurrencyService();
        });

        // --- NFT ownership services -------------------------------------------------------
        // Drivers: 'fake' (local, no chain), 'thirdweb' (real), or 'auto' (default: use
        // thirdweb when it is fully configured, otherwise fall back to the local fake).
        $this->app->bind(\App\Services\Nft\Contracts\NftMintingService::class, function () {
            $driver = config('web3.driver', 'auto');
            $thirdweb = new \App\Services\Nft\ThirdwebEngineService();
            if ($driver === 'thirdweb' || ($driver === 'auto' && $thirdweb->isLive())) {
                return $thirdweb;
            }
            return new \App\Services\Nft\FakeMintingService();
        });

        $this->app->bind(\App\Services\Nft\Contracts\MetadataStorageService::class, function () {
            $driver = config('web3.storage_driver', 'auto');
            $thirdweb = new \App\Services\Nft\ThirdwebStorageService();
            if ($driver === 'thirdweb' || ($driver === 'auto' && $thirdweb->isLive())) {
                return $thirdweb;
            }
            return new \App\Services\Nft\LocalFakeStorageService();
        });

        $this->app->bind(\App\Services\Nft\Contracts\MarketplaceService::class, function () {
            // Dollar pricing (the default) settles in platform credits, independent of the chain.
            if (\App\Models\NFT::settlesInCredits()) {
                return new \App\Services\Nft\CreditsMarketplaceService();
            }
            $driver = config('web3.driver', 'auto');
            $thirdweb = new \App\Services\Nft\ThirdwebMarketplaceService();
            if ($driver === 'thirdweb' || ($driver === 'auto' && $thirdweb->isLive())) {
                return $thirdweb;
            }
            return new \App\Services\Nft\FakeMarketplaceService();
        });
        
        // Bind App\User to App\Model\User for backward compatibility
        // This fixes issues where code references App\User but the model is in App\Model\User
        if (!class_exists('App\User')) {
            $this->app->bind('App\User', 'App\Model\User');
        }
    }

    /**
     * Bootstrap any application services.
     * TODO: Delete this once on L10.
     * @return void
     */
    public function boot()
    {
        if (!InstallerServiceProvider::checkIfInstalled()) {
            return false;
        }
        
        // Add helper functions for the views
        $this->registerBladeDirectives();
        
        UserVerify::observe(UserVerifyObserver::class);
        Withdrawal::observe(WithdrawalsObserver::class);
        PaymentRequest::observe(PaymentRequestsObserver::class);
        UserMessage::observe(UserMessagesObserver::class);
        Attachment::observe(AttachmentsObserver::class);
        Transaction::observe(TransactionsObserver::class);
        Post::observe(PostApprovalObserver::class);
        Subscription::observe(SubscriptionsObserver::class);
        User::observe(UsersObserver::class);
        Stream::observe(StreamsObserver::class);

        // Engagement: one observer across every model that represents "a user did
        // something worth rewarding". Referenced by name and guarded by class_exists so
        // that a model this install does not have (the crypto layer is optional) cannot
        // stop the application booting.
        foreach ([
            \App\Model\Post::class,
            \App\Model\PostComment::class,
            \App\Model\Reaction::class,
            \App\Model\Stream::class,
            \App\Model\Transaction::class,
            \App\Models\Video::class,
            \App\Models\VideoComment::class,
            \App\Models\CustomRequest::class,
            \App\Models\NFT::class,
        ] as $engagementModel) {
            if (class_exists($engagementModel)) {
                $engagementModel::observe(EngagementObserver::class);
            }
        }
        if(getSetting('security.enforce_app_ssl')){
            \URL::forceScheme('https');
        }
        Schema::defaultStringLength(191); // TODO: Maybe move it as the first line
        if(!InstallerServiceProvider::glck()){
            dd(base64_decode('SW52YWxpZCBzY3JpcHQgc2lnbmF0dXJl'));
        }
        // Overriding timezone, if provided
        if(getSetting('site.timezone')){
            config(['app.timezone' => getSetting('site.timezone')]);
            date_default_timezone_set(getSetting('site.timezone'));
        }
        Paginator::useBootstrap();
    }
    
    /**
     * Register custom Blade directives
     */
    protected function registerBladeDirectives()
    {
        // Safe way to get blocked list ID without relying on the method existing
        Blade::directive('getBlockedListId', function () {
            return "<?php 
                \$blockedListId = null;
                if (Auth::check()) {
                    \$user = Auth::user();
                    if (method_exists(\$user, 'lists') && \$user->lists && \$user->lists->count() > 0) {
                        \$blockedList = \$user->lists->firstWhere('type', 'blocked');
                        if (\$blockedList) {
                            \$blockedListId = \$blockedList->id;
                        }
                    } else {
                        \$blockedList = \\App\\Model\\UserList::where('user_id', \$user->id)
                            ->where('type', 'blocked')
                            ->first();
                        if (\$blockedList) {
                            \$blockedListId = \$blockedList->id;
                        }
                    }
                }
                echo \$blockedListId;
            ?>";
        });
    }
}
