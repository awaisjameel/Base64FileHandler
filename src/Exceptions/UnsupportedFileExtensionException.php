<?php

declare(strict_types=1);

namespace AwaisJameel\Base64FileHandler\Exceptions;

class UnsupportedFileExtensionException extends Base64FileHandlerException
{
    /**
     * @param  array<int, string>  $allowedExtensions
     */
    public static function forExtension(string $extension, array $allowedExtensions): self
    {
        return new self(sprintf(
            'The file extension [%s] is not allowed. Allowed extensions: %s.',
            $extension,
            implode(', ', $allowedExtensions)
        ));
    }
}
