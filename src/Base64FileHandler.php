<?php

namespace AwaisJameel\Base64FileHandler;

use AwaisJameel\Base64FileHandler\Exceptions\InvalidBase64DataException;
use AwaisJameel\Base64FileHandler\Exceptions\InvalidImageException;
use AwaisJameel\Base64FileHandler\Exceptions\MimeTypeMismatchException;
use AwaisJameel\Base64FileHandler\Exceptions\UnableToDetermineMimeTypeException;
use AwaisJameel\Base64FileHandler\Exceptions\UnableToStoreFileException;
use AwaisJameel\Base64FileHandler\Exceptions\UnsupportedFileExtensionException;
use AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException;
use AwaisJameel\MimeTypes\Facades\MimeTypes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\FilesystemException;

class Base64FileHandler
{
    /**
     * @var array{disk: string, path: string, allowed_extensions: array<int, string>, valid_image_extensions: array<int, string>}
     */
    protected array $config = [
        'disk' => 'public',
        'path' => 'uploads/',
        'allowed_extensions' => [],
        'valid_image_extensions' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],
    ];

    /**
     * Create a new Base64FileHandler instance.
     *
     * @param  array{disk?: string, path?: string, allowed_extensions?: array<int, string>, valid_image_extensions?: array<int, string>}  $config  Optional custom configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Store base64 data in the specified storage disk.
     *
     * @param  string|null  $disk  Override default disk
     * @param  string|null  $path  Override default path
     * @param  string|null  $originalName  Original filename to use
     * @param  array<int, string>|null  $allowedExtensions  Override allowed extensions
     * @return string The stored file path
     *
     * @throws InvalidBase64DataException
     * @throws MimeTypeMismatchException
     * @throws UnableToDetermineMimeTypeException
     * @throws UnknownMimeTypeOrExtensionException
     * @throws UnsupportedFileExtensionException
     * @throws UnableToStoreFileException
     */
    public function store(
        string $base64Data,
        ?string $disk = null,
        ?string $path = null,
        ?string $originalName = null,
        ?array $allowedExtensions = null
    ): string {
        $disk = $disk ?? $this->config['disk'];
        $path = $path ?? $this->config['path'];
        $allowedExtensions = $allowedExtensions ?? $this->config['allowed_extensions'];

        $info = $this->getFileInfo($base64Data);
        $extension = $info['extension'];
        $this->validateFileExtension($extension, $allowedExtensions);

        $fullPath = $this->generateUniqueFilePath($this->preparePath($path), $extension, $originalName);

        try {
            $stored = Storage::disk($disk)->put($fullPath, $info['data']);
        } catch (FilesystemException $exception) {
            throw UnableToStoreFileException::forPath($disk, $fullPath, $exception);
        }

        if (! $stored) {
            throw UnableToStoreFileException::forPath($disk, $fullPath);
        }

        return $fullPath;
    }

    /**
     * Validate if the base64 data represents a valid image.
     *
     * @param  array<int, string>|null  $validImageExtensions  Override valid image extensions
     *
     * @throws InvalidBase64DataException
     * @throws MimeTypeMismatchException
     * @throws UnableToDetermineMimeTypeException
     * @throws UnknownMimeTypeOrExtensionException
     * @throws InvalidImageException
     */
    public function isValidImage(
        string $base64Data,
        ?array $validImageExtensions = null
    ): bool {
        $validExtensions = $validImageExtensions ?? $this->config['valid_image_extensions'];

        $info = $this->getFileInfo($base64Data);
        $extension = $info['extension'];

        if (! in_array(Str::lower($extension), array_map(Str::lower(...), $validExtensions), true)) {
            throw InvalidImageException::forExtension($extension, $validExtensions);
        }

        if (! str_starts_with($info['mime'], 'image/') || @getimagesizefromstring($info['data']) === false) {
            throw InvalidImageException::forData();
        }

        return true;
    }

    /**
     * Get file info from base64 data.
     *
     * @return array{mime: string, extension: string, size: int, data: string}
     *
     * @throws InvalidBase64DataException
     * @throws MimeTypeMismatchException
     * @throws UnableToDetermineMimeTypeException
     * @throws UnknownMimeTypeOrExtensionException
     */
    public function getFileInfo(string $base64Data): array
    {
        $decodedData = $this->decodeBase64($base64Data);
        $mime = $this->getMimeType($base64Data, $decodedData);
        $extension = $this->getExtensionFromMime($mime);

        return [
            'mime' => $mime,
            'extension' => $extension,
            'size' => strlen($decodedData),
            'data' => $decodedData,
        ];
    }

    /**
     * Decode base64 data.
     *
     * @throws InvalidBase64DataException
     */
    protected function decodeBase64(string $base64Data): string
    {
        $file = base64_decode($this->parseDataUri($base64Data)['payload'], true);

        if ($file === false) {
            throw InvalidBase64DataException::forData();
        }

        return $file;
    }

    /**
     * Get MIME type from base64 data.
     *
     * @throws InvalidBase64DataException
     * @throws MimeTypeMismatchException
     * @throws UnableToDetermineMimeTypeException
     */
    protected function getMimeType(string $base64Data, ?string $decodedData = null): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($decodedData ?? $this->decodeBase64($base64Data));

        if (! $mime) {
            throw UnableToDetermineMimeTypeException::forData();
        }

        $declaredMime = $this->parseDataUri($base64Data)['mime'];

        if ($declaredMime !== null && $declaredMime !== $mime) {
            throw MimeTypeMismatchException::forTypes($declaredMime, $mime);
        }

        return $mime;
    }

    /**
     * @return array{mime: ?string, payload: string}
     */
    protected function parseDataUri(string $base64Data): array
    {
        if (strncasecmp($base64Data, 'data:', 5) !== 0) {
            return ['mime' => null, 'payload' => $base64Data];
        }

        if (preg_match('/\Adata:([^;,]+)(?:;[^,;]+)*;base64,(.*)\z/is', $base64Data, $matches) !== 1) {
            throw InvalidBase64DataException::forData();
        }

        return ['mime' => strtolower($matches[1]), 'payload' => $matches[2]];
    }

    /**
     * Get file extension from MIME type.
     *
     * @throws UnknownMimeTypeOrExtensionException
     */
    protected function getExtensionFromMime(string $mime): string
    {
        return MimeTypes::getExtensionOrMime($mime);
    }

    /**
     * Validate file extension against allowed list.
     *
     * @param  array<int, string>  $allowedExtensions
     *
     * @throws UnsupportedFileExtensionException
     */
    protected function validateFileExtension(string $extension, array $allowedExtensions): void
    {
        if ($allowedExtensions === []) {
            return;
        }

        if (! in_array(Str::lower($extension), array_map(Str::lower(...), $allowedExtensions), true)) {
            throw UnsupportedFileExtensionException::forExtension($extension, $allowedExtensions);
        }
    }

    /**
     * Prepare storage path.
     */
    protected function preparePath(string $path): string
    {
        return rtrim($path, '/').'/';
    }

    /**
     * Generate a unique file path, safe from collisions even under concurrent requests.
     */
    protected function generateUniqueFilePath(string $path, string $extension, ?string $originalName): string
    {
        $fileName = $originalName ? Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) : '';

        $uniqueSuffix = (string) Str::ulid();

        return $path.($fileName !== '' ? $fileName.'_'.$uniqueSuffix : $uniqueSuffix).'.'.$extension;
    }
}
