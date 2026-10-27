<?php

declare(strict_types=1);

namespace AwaisJameel\Base64FileHandler\Exceptions;

class MimeTypeMismatchException extends Base64FileHandlerException
{
    public static function forTypes(string $declared, string $detected): self
    {
        return new self("The declared MIME type [{$declared}] does not match the decoded content [{$detected}].");
    }
}
