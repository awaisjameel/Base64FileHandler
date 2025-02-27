<?php

namespace AwaisJameel\Base64FileHandler;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Base64FileHandler
{
    /**
     * @var array Default configuration
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
     * @param array $config Optional custom configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Store base64 data in the specified storage disk.
     *
     * @param string $base64Data
     * @param string|null $disk Override default disk
     * @param string|null $path Override default path
     * @param string|null $originalName Original filename to use
     * @param array|null $allowedExtensions Override allowed extensions
     * @return string The stored file path
     * @throws Exception
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

        $path = $this->preparePath($path);
        $fullPath = $this->generateUniqueFilePath($path, $extension, $originalName);

        $this->ensureDirectoryExists($disk, $path);
        $this->storeFile($disk, $fullPath, $file);

        return $fullPath;
    }

    /**
     * Validate if the base64 data represents a valid image.
     *
     * @param string $base64Data
     * @param array|null $validImageExtensions Override valid image extensions
     * @return bool
     * @throws Exception
     */
    public function isValidImage(
        string $base64Data,
        ?array $validImageExtensions = null
    ): bool {
        $validExtensions = $validImageExtensions ?? $this->config['valid_image_extensions'];

        $this->decodeBase64($base64Data);
        $extension = $this->getFileExtension($base64Data);

        if (!in_array($extension, $validExtensions)) {
            throw new Exception('Invalid image file extension!');
        }

        return true;
    }

    /**
     * Get file info from base64 data.
     *
     * @param string $base64Data
     * @return array{mime: string, extension: string, size: int, data: string}
     * @throws Exception
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
            'data' => $decodedData
        ];
    }

    /**
     * Decode base64 data.
     *
     * @param string $base64Data
     * @return string
     * @throws Exception
     */
    protected function decodeBase64(string $base64Data): string
    {
        $filteredBase64Data = preg_replace('#^data:([^;]+);base64,#', '', $base64Data);
        $file = base64_decode($filteredBase64Data, true);

        if ($file === false) {
            throw new Exception('Invalid base64 data!');
        }

        return $file;
    }

    /**
     * Get MIME type from base64 data.
     *
     * @param string $base64Data
     * @return string
     * @throws Exception
     */
    protected function getMimeType(string $base64Data): string
    {
        preg_match('#^data:([^;]+);base64,#', $base64Data, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        // For data without a MIME prefix, try to detect
        $tempFile = tempnam(sys_get_temp_dir(), 'b64');
        file_put_contents($tempFile, $this->decodeBase64($base64Data));
        $mime = mime_content_type($tempFile);
        unlink($tempFile);

        if (!$mime) {
            throw new Exception('Unable to determine MIME type');
        }

        return $mime;
    }

    /**
     * Get file extension from MIME type.
     *
     * @param string $mime
     * @return string
     */
    protected function getExtensionFromMime(string $mime): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            // Add more MIME types as needed
        ];

        return $mimeMap[$mime] ?? 'bin';
    }

    /**
     * Get file extension from base64 data.
     *
     * @param string $base64Data
     * @return string
     * @throws Exception
     */
    protected function getFileExtension(string $base64Data): string
    {
        $mime = $this->getMimeType($base64Data);
        return $this->getExtensionFromMime($mime);
    }

    /**
     * Validate file extension against allowed list.
     *
     * @param string $extension
     * @param array $allowedExtensions
     * @throws Exception
     */
    protected function validateFileExtension(string $extension, array $allowedExtensions): void
    {
        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
            throw new Exception('File extension not allowed!');
        }
    }

    /**
     * Prepare storage path.
     *
     * @param string $path
     * @return string
     */
    protected function preparePath(string $path): string
    {
        return rtrim($path, '/') . '/';
    }

    /**
     * Generate unique file path.
     *
     * @param string $path
     * @param string $extension
     * @param string|null $originalName
     * @return string
     */
    protected function generateUniqueFilePath(string $path, string $extension, ?string $originalName): string
    {
        $fileName = $originalName
            ? Str::slug(pathinfo($originalName, PATHINFO_FILENAME))
            : (string) Str::uuid();

        return $path . $fileName . '_' . time() . '.' . $extension;
    }

    /**
     * Ensure directory exists.
     *
     * @param string $disk
     * @param string $path
     */
    protected function ensureDirectoryExists(string $disk, string $path): void
    {
        if (!Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->makeDirectory($path);
        }
    }

    /**
     * Store file to disk.
     *
     * @param string $disk
     * @param string $fullPath
     * @param string $file
     * @throws Exception
     */
    protected function storeFile(string $disk, string $fullPath, string $file): void
    {
        $stored = Storage::disk($disk)->put($fullPath, $file);

        if (!$stored) {
            throw new Exception('Unable to store file to the path');
        }
    }
}