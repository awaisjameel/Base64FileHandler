<?php

namespace AwaisJameel\Base64FileHandler\Tests;

use AwaisJameel\Base64FileHandler\Base64FileHandlerServiceProvider;
use AwaisJameel\MimeTypes\MimeTypesServiceProvider;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Force awaisjameel/mimetypes to resolve from its bundled offline
        // snapshot instead of making a real HTTP request to the Apache
        // mime.types source on every test run.
        Http::fake(['*' => Http::response('', 500)]);
    }

    protected function getPackageProviders($app)
    {
        return [
            MimeTypesServiceProvider::class,
            Base64FileHandlerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
