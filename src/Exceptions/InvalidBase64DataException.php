<?php

declare(strict_types=1);

namespace AwaisJameel\Base64FileHandler\Exceptions;

class InvalidBase64DataException extends Base64FileHandlerException
{
    public static function forData(): self
    {
        return new self('The given string is not valid base64 data.');
    }
}
