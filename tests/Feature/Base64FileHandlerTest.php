<?php

namespace AwaisJameel\Base64FileHandler\Tests\Feature;

use AwaisJameel\Base64FileHandler\Base64FileHandler;
use AwaisJameel\Base64FileHandler\Exceptions\InvalidBase64DataException;
use AwaisJameel\Base64FileHandler\Exceptions\InvalidImageException;
use AwaisJameel\Base64FileHandler\Exceptions\MimeTypeMismatchException;
use AwaisJameel\Base64FileHandler\Exceptions\UnableToStoreFileException;
use AwaisJameel\Base64FileHandler\Exceptions\UnsupportedFileExtensionException;
use AwaisJameel\Base64FileHandler\Facades\Base64FileHandler as Base64FileHandlerFacade;
use AwaisJameel\Base64FileHandler\Tests\TestCase;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;

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
        $this->validImageBase64 = 'data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/../Stubs/test-image.png'));

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
        $this->expectException(InvalidBase64DataException::class);
        $this->handler->store($this->invalidBase64);
    }

    public function test_can_validate_image()
    {
        $this->assertTrue($this->handler->isValidImage($this->validImageBase64));
    }

    public function test_throws_exception_for_invalid_image()
    {
        $this->expectException(InvalidBase64DataException::class);
        $this->handler->isValidImage($this->invalidBase64);
    }

    public function test_throws_exception_for_disallowed_image_extension()
    {
        $this->expectException(InvalidImageException::class);
        $this->handler->isValidImage($this->validImageBase64, ['pdf']);
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
        $this->expectException(UnsupportedFileExtensionException::class);

        $this->handler->store(
            $this->validImageBase64,
            null,
            null,
            null,
            ['pdf'] // Only allow PDFs
        );
    }

    public function test_allowed_extensions_are_matched_case_insensitively()
    {
        $filePath = $this->handler->store(
            $this->validImageBase64,
            null,
            null,
            null,
            ['PNG']
        );

        Storage::disk('public')->assertExists($filePath);
    }

    public function test_uses_original_filename()
    {
        $originalName = 'test-image.png';
        $filePath = $this->handler->store($this->validImageBase64, null, null, $originalName);

        $this->assertStringContainsString('test-image', $filePath);
    }

    public function test_generates_unique_paths_for_repeated_uploads_of_the_same_name()
    {
        $originalName = 'test-image.png';

        $firstPath = $this->handler->store($this->validImageBase64, null, null, $originalName);
        $secondPath = $this->handler->store($this->validImageBase64, null, null, $originalName);

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertExists($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_container_resolution_honours_published_configuration()
    {
        Storage::fake('config-disk');
        config()->set('base64filehandler.disk', 'config-disk');

        $filePath = Base64FileHandlerFacade::store($this->validImageBase64);

        Storage::disk('config-disk')->assertExists($filePath);
    }

    public function test_container_resolution_honours_configured_allowed_extensions()
    {
        config()->set('base64filehandler.allowed_extensions', ['pdf']);

        $this->expectException(UnsupportedFileExtensionException::class);

        Base64FileHandlerFacade::store($this->validImageBase64);
    }

    public function test_inspects_and_stores_raw_base64_without_a_mime_prefix(): void
    {
        $raw = explode(',', $this->validImageBase64, 2)[1];
        $info = $this->handler->getFileInfo($raw);
        $path = $this->handler->store($raw);

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(base64_decode($raw, true), $info['data']);
        $this->assertSame(strlen($info['data']), $info['size']);
        $this->assertSame($info['data'], Storage::disk('public')->get($path));
        $this->assertTrue($this->handler->isValidImage($raw));
    }

    public function test_rejects_a_spoofed_mime_label_without_writing_a_file(): void
    {
        try {
            $this->handler->store('data:image/png;base64,'.base64_encode('This is a plain text document.'));
            $this->fail('Expected a MIME mismatch.');
        } catch (MimeTypeMismatchException $exception) {
            $this->assertStringContainsString('text/plain', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_image_validation_rejects_a_spoofed_mime_label(): void
    {
        $this->expectException(MimeTypeMismatchException::class);

        $this->handler->isValidImage('data:image/png;base64,'.base64_encode('This is plain text.'));
    }

    public function test_inspection_rejects_a_conflicting_label_on_a_real_image(): void
    {
        $this->expectException(MimeTypeMismatchException::class);

        $this->handler->getFileInfo(str_replace('image/png', 'image/jpeg', $this->validImageBase64));
    }

    public function test_accepts_case_insensitive_data_uri_metadata(): void
    {
        $uri = str_replace(['data:', 'image/png', ';base64,'], ['DATA:', 'IMAGE/PNG', ';BASE64,'], $this->validImageBase64);

        $this->assertSame('image/png', $this->handler->getFileInfo($uri)['mime']);
    }

    public function test_accepts_data_uri_parameters(): void
    {
        $uri = 'data:text/plain;charset=UTF-8;base64,'.base64_encode('This is plain text.');

        $this->assertSame('text/plain', $this->handler->getFileInfo($uri)['mime']);
    }

    public function test_rejects_a_malformed_data_uri(): void
    {
        $this->expectException(InvalidBase64DataException::class);

        $this->handler->getFileInfo('data:image/png,not-base64');
    }

    public function test_a_non_image_cannot_be_accepted_by_a_custom_image_extension_list(): void
    {
        $this->expectException(InvalidImageException::class);

        $this->handler->isValidImage(base64_encode('This is plain text.'), ['txt']);
    }

    public function test_converts_a_rejected_storage_write_to_a_package_exception(): void
    {
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk);
        $this->expectException(UnableToStoreFileException::class);

        $this->handler->store($this->validImageBase64);
    }

    public function test_preserves_the_cause_of_a_thrown_storage_failure(): void
    {
        $cause = UnableToWriteFile::atLocation('uploads/image.png', 'Read-only disk');
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andThrow($cause);
        Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk);

        try {
            $this->handler->store($this->validImageBase64);
            $this->fail('Expected a storage exception.');
        } catch (UnableToStoreFileException $exception) {
            $this->assertSame($cause, $exception->getPrevious());
        }
    }
}
