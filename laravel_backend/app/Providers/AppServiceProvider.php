<?php

namespace App\Providers;

use App\Repositories\Interface\ServicePartnerRepositoryInterface;
use App\Repositories\Interface\UserRepositoryInterface;
use App\Repositories\ServicePartnerRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ServicePartnerRepositoryInterface::class, ServicePartnerRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
