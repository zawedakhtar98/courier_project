<?php

namespace App\Providers;

use App\Repositories\CountryRepository;
use App\Repositories\Interface\CountryRepositoryInterface;
use App\Repositories\Interface\RateCalRepositoryInterface;
use App\Repositories\Interface\ServicePartnerRepositoryInterface;
use App\Repositories\Interface\ServicePartnerZoneRateRepositoryInterface;
use App\Repositories\Interface\ShipmentChargesMasterRespositoryInterface;
use App\Repositories\Interface\UserRepositoryInterface;
use App\Repositories\Interface\ZoneMasterRepositoryInterface;
use App\Repositories\RateCalRepository;
use App\Repositories\ServicePartnerRepository;
use App\Repositories\ServicePartnerZoneRateRepository;
use App\Repositories\ShipmentChargesMasterRespository;
use App\Repositories\UserRepository;
use App\Repositories\ZoneMasterRepository;
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
        $this->app->bind(ServicePartnerZoneRateRepositoryInterface::class, ServicePartnerZoneRateRepository::class);
        $this->app->bind(ShipmentChargesMasterRespositoryInterface::class, ShipmentChargesMasterRespository::class);
        $this->app->bind(RateCalRepositoryInterface::class, RateCalRepository::class);
        $this->app->bind(ZoneMasterRepositoryInterface::class, ZoneMasterRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
