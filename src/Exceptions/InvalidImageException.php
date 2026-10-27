<?php

declare(strict_types=1);

namespace AwaisJameel\Base64FileHandler\Exceptions;

class InvalidImageException extends Base64FileHandlerException
{
    public static function forData(): self
    {
        return new self('The decoded data does not contain a recognizable image.');
    }

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
