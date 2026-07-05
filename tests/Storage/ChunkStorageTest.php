<?php

namespace Tests\Storage;

use Illuminate\Filesystem\FilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ChunkStorageTest extends TestCase
{
    public function testDirectoryForFileUsesShardAndLeafForShortIds()
    {
        $storage = $this->makeStorage();

        $this->assertSame('chunks/abcd/abcd/', $storage->directoryForFile('abcd'));
        $this->assertSame('chunks/abcd/abcd.part', $storage->mergedFilePathForFile('abcd'));
    }

    public function testDirectoryForFileAddsAdditionalSegmentsForLongIds()
    {
        $storage = $this->makeStorage();
        $fileId = 'abcd'.str_repeat('e', 40);

        $this->assertSame(
            'chunks/abcd/'.str_repeat('e', 32).'/'.str_repeat('e', 8).'/',
            $storage->directoryForFile($fileId)
        );
        $this->assertSame(
            'chunks/abcd/'.str_repeat('e', 32).'/'.str_repeat('e', 8).'.part',
            $storage->mergedFilePathForFile($fileId)
        );
    }

    public function testOldChunkFilesScansNestedDirectoriesRecursively()
    {
        $storage = $this->makeStorage([
            'files' => [
                'chunks/abcd/upload/1.part',
                'chunks/abcd/upload/2.part',
                'chunks/abcd/upload.txt',
            ],
            'lastModified' => [
                'chunks/abcd/upload/1.part' => time(),
                'chunks/abcd/upload/2.part' => time(),
            ],
            'clearTimestampString' => '+1 minute',
        ]);

        $this->assertCount(2, $storage->oldChunkFiles());
    }

    public function testDeleteEmptyDirectoriesRemovesNestedUploadDirectories()
    {
        $storage = $this->makeStorage([
            'directories' => [
                'chunks/abcd/upload' => [],
                'chunks/abcd' => [],
            ],
        ], $disk);

        $deletedDirectories = [];
        $disk->expects($this->exactly(2))
            ->method('deleteDirectory')
            ->willReturnCallback(function ($directory) use (&$deletedDirectories) {
                $deletedDirectories[] = $directory;

                return true;
            });

        $storage->deleteEmptyDirectories('chunks/abcd/upload');

        $this->assertSame(['chunks/abcd/upload', 'chunks/abcd'], $deletedDirectories);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return ChunkStorage
     */
    private function makeStorage(array $overrides = [], &$disk = null)
    {
        $disk = $this->getMockBuilder(FilesystemAdapter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdapter', 'path', 'files', 'lastModified', 'getDriver', 'directories', 'deleteDirectory'])
            ->getMock();

        $disk->method('getAdapter')
            ->willReturn($this->createLocalAdapterDouble());
        $disk->method('path')
            ->with('')
            ->willReturn('/tmp/');
        $disk->method('getDriver')
            ->willReturn(null);
        $disk->method('files')
            ->willReturnCallback(function ($directory, $recursive = false) use ($overrides) {
                if ('chunks/' === $directory && true === $recursive) {
                    return $overrides['files'] ?? [];
                }

                return $overrides['files_by_directory'][$directory] ?? [];
            });
        $disk->method('directories')
            ->willReturnCallback(function ($directory) use ($overrides) {
                return $overrides['directories'][$directory] ?? [];
            });
        $disk->method('lastModified')
            ->willReturnCallback(function ($path) use ($overrides) {
                return $overrides['lastModified'][$path] ?? time();
            });

        $config = $this->getMockBuilder(AbstractConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['chunksStorageDirectory', 'clearTimestampString'])
            ->getMockForAbstractClass();
        $config->method('chunksStorageDirectory')
            ->willReturn('chunks');
        $config->method('clearTimestampString')
            ->willReturn($overrides['clearTimestampString'] ?? '-1 minute');

        return new ChunkStorage($disk, $config);
    }

    private function createLocalAdapterDouble()
    {
        if (class_exists(\League\Flysystem\Local\LocalFilesystemAdapter::class)) {
            return $this->createMock(\League\Flysystem\Local\LocalFilesystemAdapter::class);
        }

        if (class_exists(\League\Flysystem\Adapter\Local::class)) {
            return $this->createMock(\League\Flysystem\Adapter\Local::class);
        }

        $this->fail('No supported local filesystem adapter class is available for ChunkStorage tests.');
    }
}
