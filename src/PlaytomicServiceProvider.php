<?php

declare(strict_types=1);

namespace Divino11\Playtomic;

use Divino11\Playtomic\Contracts\PlaytomicAuthClientInterface;
use Divino11\Playtomic\Contracts\PlaytomicClientInterface;
use Divino11\Playtomic\Services\PlaytomicClient;
use Illuminate\Support\ServiceProvider;

class PlaytomicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/playtomic.php', 'playtomic');

        $this->app->bind(PlaytomicClientInterface::class, PlaytomicClient::class);
        $this->app->bind(PlaytomicAuthClientInterface::class, PlaytomicClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/playtomic.php' => config_path('playtomic.php'),
            ], 'playtomic-config');
        }
    }
}
