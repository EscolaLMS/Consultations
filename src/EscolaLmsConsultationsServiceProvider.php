<?php

namespace EscolaLms\Consultations;

use EscolaLms\Consultations\Repositories\ConsultationRepository;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationRepositoryContract;
use EscolaLms\Consultations\Services\ConsultationService;
use EscolaLms\Consultations\Services\Contracts\ConsultationServiceContract;
use Illuminate\Support\ServiceProvider;

/**
 * SWAGGER_VERSION
 */
class EscolaLmsConsultationsServiceProvider extends ServiceProvider
{
    public const SERVICES = [
        ConsultationServiceContract::class => ConsultationService::class
    ];
    public const REPOSITORIES = [
        ConsultationRepositoryContract::class => ConsultationRepository::class
    ];

    public $singletons = self::SERVICES + self::REPOSITORIES;

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('escolalms_consultations.php'),
        ], 'escolalms_consultations.config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'escolalms_consultations');
        $this->app->register(AuthServiceProvider::class);
    }
}
