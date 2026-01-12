<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repository\TicketRepository;
use App\Repository\CityRepository;
use App\Repository\Home\HomeContentRepository;
use App\Repository\Site\TranslationRepository;
use App\Repository\Site\ImageRepository;
use App\Repository\Races\ToursRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Регистрация TicketRepository
        $this->app->singleton(TicketRepository::class, function ($app) {
            return new TicketRepository();
        });
        
        // Регистрация CityRepository
        $this->app->singleton(CityRepository::class, function ($app) {
            return new CityRepository();
        });
        
        // Регистрация HomeContentRepository
        $this->app->singleton(HomeContentRepository::class, function ($app) {
            return new HomeContentRepository();
        });
        
        // Регистрация TranslationRepository
        $this->app->singleton(TranslationRepository::class, function ($app) {
            return new TranslationRepository();
        });
        
        // Регистрация ImageRepository
        $this->app->singleton(ImageRepository::class, function ($app) {
            return new ImageRepository();
        });
        
        // Регистрация ToursRepository
        $this->app->singleton(ToursRepository::class, function ($app) {
            return new ToursRepository();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}