<?php

declare(strict_types=1);

namespace AwaisJameel\Base64FileHandler\Exceptions;

class InvalidImageException extends Base64FileHandlerException
{
    /**
     * @param  array<int, string>  $validImageExtensions
     */
    public static function forExtension(string $extension, array $validImageExtensions): self
    {
        return new self(sprintf(
            'The file extension [%s] is not a valid image extension. Valid image extensions: %s.',
            $extension,
            implode(', ', $validImageExtensions)
        ));
    }
}
