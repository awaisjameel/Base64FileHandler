<?php

namespace AwaisJameel\Base64FileHandler;

use AwaisJameel\Base64FileHandler\Exceptions\InvalidBase64DataException;
use AwaisJameel\Base64FileHandler\Exceptions\InvalidImageException;
use AwaisJameel\Base64FileHandler\Exceptions\UnableToDetermineMimeTypeException;
use AwaisJameel\Base64FileHandler\Exceptions\UnableToStoreFileException;
use AwaisJameel\Base64FileHandler\Exceptions\UnsupportedFileExtensionException;
use AwaisJameel\MimeTypes\Exceptions\UnknownMimeTypeOrExtensionException;
use AwaisJameel\MimeTypes\Facades\MimeTypes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * @param  array<string, mixed>  $config  Optional custom configuration
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

        $file = $this->decodeBase64($base64Data);
        $extension = $this->getFileExtension($base64Data);
        $this->validateFileExtension($extension, $allowedExtensions);

        $fullPath = $this->generateUniqueFilePath($this->preparePath($path), $extension, $originalName);

        if (! Storage::disk($disk)->put($fullPath, $file)) {
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
     * @throws UnableToDetermineMimeTypeException
     * @throws UnknownMimeTypeOrExtensionException
     * @throws InvalidImageException
     */
    public function isValidImage(
        string $base64Data,
        ?array $validImageExtensions = null
    ): bool {
        $validExtensions = $validImageExtensions ?? $this->config['valid_image_extensions'];

        $this->decodeBase64($base64Data);
        $extension = $this->getFileExtension($base64Data);

        if (! in_array(Str::lower($extension), array_map(Str::lower(...), $validExtensions), true)) {
            throw InvalidImageException::forExtension($extension, $validExtensions);
        }

        return true;
    }

    /**
     * Get file info from base64 data.
     *
     * @return array{mime: string, extension: string, size: int, data: string}
     *
     * @throws InvalidBase64DataException
     * @throws UnableToDetermineMimeTypeException
     * @throws UnknownMimeTypeOrExtensionException
     */
    public function getFileInfo(string $base64Data): array
    {
        $decodedData = $this->decodeBase64($base64Data);
        $mime = $this->getMimeType($base64Data);
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
        $filteredBase64Data = preg_replace('#^data:([^;]+);base64,#', '', $base64Data);
        $file = base64_decode($filteredBase64Data, true);

        if ($file === false) {
            throw InvalidBase64DataException::forData();
        }

        return $file;
    }

    /**
     * Get MIME type from base64 data.
     *
     * @throws InvalidBase64DataException
     * @throws UnableToDetermineMimeTypeException
     */
    protected function getMimeType(string $base64Data): string
    {
        preg_match('#^data:([^;]+);base64,#', $base64Data, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        // For data without a MIME prefix, fall back to sniffing the decoded content.
        $tempFile = tempnam(sys_get_temp_dir(), 'b64');

        try {
            file_put_contents($tempFile, $this->decodeBase64($base64Data));
            $mime = mime_content_type($tempFile);
        } finally {
            unlink($tempFile);
        }

        if (! $mime) {
            throw UnableToDetermineMimeTypeException::forData();
        }

        return $mime;
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
     * Get file extension from base64 data.
     *
     * @throws InvalidBase64DataException
     * @throws UnableToDetermineMimeTypeException
     * @throws UnknownMimeTypeOrExtensionException
     */
    protected function getFileExtension(string $base64Data): string
    {
        $mime = $this->getMimeType($base64Data);

        return $this->getExtensionFromMime($mime);
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
