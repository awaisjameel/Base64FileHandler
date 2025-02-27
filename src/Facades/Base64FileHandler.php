<?php

namespace AwaisJameel\Base64FileHandler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AwaisJameel\Base64FileHandler\Base64FileHandler
 */
class Base64FileHandler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AwaisJameel\Base64FileHandler\Base64FileHandler::class;
    }
}
