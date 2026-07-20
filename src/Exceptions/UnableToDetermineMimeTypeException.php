<?php

declare(strict_types=1);

namespace AwaisJameel\Base64FileHandler\Exceptions;

class UnableToDetermineMimeTypeException extends Base64FileHandlerException
{
    public static function forData(): self
    {
        return new self('Unable to determine the MIME type of the given base64 data.');
    }
}
