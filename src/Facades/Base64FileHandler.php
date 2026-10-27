<?php

namespace AwaisJameel\Base64FileHandler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string store(string $base64Data, ?string $disk = null, ?string $path = null, ?string $originalName = null, array<int, string>|null $allowedExtensions = null)
 * @method static bool isValidImage(string $base64Data, array<int, string>|null $validImageExtensions = null)
 * @method static array{mime: string, extension: string, size: int, data: string} getFileInfo(string $base64Data)
 *
 * @see \AwaisJameel\Base64FileHandler\Base64FileHandler
 */
class Base64FileHandler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AwaisJameel\Base64FileHandler\Base64FileHandler::class;
    }
}
