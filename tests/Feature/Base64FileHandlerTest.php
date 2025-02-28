<?php

namespace AwaisJameel\Base64FileHandler\Tests\Feature;

use AwaisJameel\Base64FileHandler\Base64FileHandler;
use AwaisJameel\Base64FileHandler\Tests\TestCase;
use Exception;
use Illuminate\Support\Facades\Storage;

class Base64FileHandlerTest extends TestCase
{
    private Base64FileHandler $handler;

    private string $validImageBase64;

    private string $invalidBase64;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->handler = new Base64FileHandler;

        // Valid base64 PNG image
        $this->validImageBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/../stubs/test-image.png'));

        // Invalid base64 string
        $this->invalidBase64 = 'invalid-base64-string';
    }

    public function test_can_store_valid_base64_file()
    {
        $filePath = $this->handler->store($this->validImageBase64);

        $this->assertNotEmpty($filePath);
        Storage::disk('public')->assertExists($filePath);
    }

    public function test_throws_exception_for_invalid_base64()
    {
        $this->expectException(Exception::class);
        $this->handler->store($this->invalidBase64);
    }

    public function test_can_validate_image()
    {
        $this->assertTrue($this->handler->isValidImage($this->validImageBase64));
    }

    public function test_throws_exception_for_invalid_image()
    {
        $this->expectException(Exception::class);
        $this->handler->isValidImage($this->invalidBase64);
    }

    public function test_can_get_file_info()
    {
        $fileInfo = $this->handler->getFileInfo($this->validImageBase64);

        $this->assertEquals('image/png', $fileInfo['mime']);
        $this->assertEquals('png', $fileInfo['extension']);
        $this->assertIsInt($fileInfo['size']);
        $this->assertNotEmpty($fileInfo['data']);
    }

    public function test_respects_custom_disk()
    {
        Storage::fake('custom');

        $filePath = $this->handler->store($this->validImageBase64, 'custom');

        Storage::disk('custom')->assertExists($filePath);
    }

    public function test_respects_custom_path()
    {
        $customPath = 'custom/path/';
        $filePath = $this->handler->store($this->validImageBase64, null, $customPath);

        $this->assertStringStartsWith($customPath, $filePath);
    }

    public function test_respects_allowed_extensions()
    {
        $this->expectException(Exception::class);

        $this->handler->store(
            $this->validImageBase64,
            null,
            null,
            null,
            ['pdf'] // Only allow PDFs
        );
    }

    public function test_uses_original_filename()
    {
        $originalName = 'test-image.png';
        $filePath = $this->handler->store($this->validImageBase64, null, null, $originalName);

        $this->assertStringContainsString('test-image', $filePath);
    }
}
