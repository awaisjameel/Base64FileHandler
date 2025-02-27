<?php

namespace AwaisJameel\Base64FileHandler;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AwaisJameel\Base64FileHandler\Commands\Base64FileHandlerCommand;

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
}