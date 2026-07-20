<?php

namespace AwaisJameel\Base64FileHandler;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class Base64FileHandlerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('base64filehandler')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(
            Base64FileHandler::class,
            fn ($app) => new Base64FileHandler((array) $app['config']->get('base64filehandler', []))
        );
    }
}
