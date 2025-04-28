<?php

namespace App\Providers;

use App\Interfaces\BaseRepositoryInterface;
use App\Repositories\BaseRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->bind(
            \App\Repositories\Interfaces\LedgerRepositoryInterface::class,
            \App\Repositories\LedgerRepository::class
        );

        $this->app->bind(
            \App\Services\Interfaces\LedgerServiceInterface::class,
            \App\Services\LedgerService::class
        );
    }

}
