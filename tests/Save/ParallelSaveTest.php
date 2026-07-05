<?php

namespace ChunkTests\Save;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Save\ChunkSave;
use Pion\Laravel\ChunkUpload\Save\ParallelSave;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ParallelSaveTest extends TestCase
{
    public function testParallelChunkFileIdExcludesChunkIndex()
    {
        $save = (new \ReflectionClass(ParallelSaveTestProxy::class))->newInstanceWithoutConstructor();

        $this->setProperty($save, 'chunkFileName', 'test-upload.4.part', ChunkSave::class);

        $this->assertSame('test-upload', $save->getChunkFileId());
    }

    public function testNonParallelChunkFileIdPreservesNumericSuffix()
    {
        $save = (new \ReflectionClass(ChunkSave::class))->newInstanceWithoutConstructor();

        $this->setProperty($save, 'chunkFileName', 'test-upload.12345.part', ChunkSave::class);

        $this->assertSame('test-upload.12345', $save->getChunkFileId());
    }

    public function testSavedChunkFilesOnlyIncludeCurrentUploadChunks()
    {
        $save = (new \ReflectionClass(ParallelSaveTestProxy::class))->newInstanceWithoutConstructor();

        $handler = $this->getMockBuilder(AbstractHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'startSaving',
                'getChunkFileName',
                'isFirstChunk',
                'isLastChunk',
                'isChunkedUpload',
                'getPercentageDone',
                'requiresFinalChunkOnLastChunk',
            ])
            ->getMockForAbstractClass();
        $handler->method('getChunkFileName')
            ->willReturn('test-upload.4.part');

        $chunkStorage = $this->getMockBuilder(ChunkStorage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['directoryForFile', 'filesByDirectory'])
            ->getMock();
        $chunkStorage->method('directoryForFile')
            ->with('test-upload')
            ->willReturn('chunks/test/-upload/');
        $chunkStorage->expects($this->once())
            ->method('filesByDirectory')
            ->with('chunks/test/-upload/')
            ->willReturn(new Collection([
                'chunks/test/-upload/4.part',
                'chunks/test/-upload/5.part',
                'chunks/test/-upload/6.part',
            ]));

        $this->setProperty($save, 'handler', $handler, \Pion\Laravel\ChunkUpload\Save\AbstractSave::class);
        $this->setProperty($save, 'chunkStorage', $chunkStorage, ParallelSave::class);
        $this->setProperty($save, 'chunkFileName', 'test-upload.4.part', ChunkSave::class);

        $this->assertSame([
            'chunks/test/-upload/4.part',
            'chunks/test/-upload/5.part',
            'chunks/test/-upload/6.part',
        ], $save->savedChunkFiles()->values()->all());
    }

    private function setProperty($object, $property, $value, $className)
    {
        $reflection = new \ReflectionClass($className);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($object, $value);
    }
}

class ParallelSaveTestProxy extends ParallelSave
{
    public function savedChunkFiles()
    {
        return $this->getSavedChunksFiles();
    }
}
