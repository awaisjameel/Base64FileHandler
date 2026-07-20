<?php

declare(strict_types=1);

namespace AwaisJameel\Base64FileHandler\Exceptions;

class UnableToStoreFileException extends Base64FileHandlerException
{
    public static function forPath(string $disk, string $path): self
    {
        return new self(sprintf('Unable to store the file at [%s] on disk [%s].', $path, $disk));
    }
}
