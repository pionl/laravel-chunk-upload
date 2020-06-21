<?php

namespace ChunkTests\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Handler\DropZoneUploadHandler;

class DropZoneUploadHandlerTest extends TestCase
{
    protected $file = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = UploadedFile::fake()->create('test');
    }

    public function testInitWithoutRequestData()
    {
        $request = Request::create('test', 'POST', [], [], [], []);

        $contentRange = new DropZoneUploadHandler($request, $this->file, new FileConfig());

        $this->assertEquals(1, $contentRange->getTotalChunks());
        $this->assertEquals(1, $contentRange->getCurrentChunk());
        $this->assertEquals(0, $contentRange->getPercentageDone());
        $contentRange->setPercentageDone(100);
        $this->assertEquals(100, $contentRange->getPercentageDone());
        $this->assertFalse($contentRange->isChunkedUpload());
    }

    /**
     * Checks if canBeUsedForRequest returns false when data is missing.
     */
    public function testCanBeUsedForInvalidRequest()
    {
        $request = Request::create('test', 'POST', [], [], [], []);
        $this->assertFalse(DropZoneUploadHandler::canBeUsedForRequest($request));
    }

    /**
     * Checks if canBeUsedForRequest returns false when content-range is missing.
     */
    public function testCanBeUsedForInvalidRequestPartDaata()
    {
        $request = Request::create('test', 'POST', [
            DropZoneUploadHandler::CHUNK_UUID_INDEX => 'test',
        ], [], [], []);
        $this->assertFalse(DropZoneUploadHandler::canBeUsedForRequest($request));
    }

    /**
     * Checks if canBeUsedForRequest returns false when content-range is missing.
     */
    public function testCanBeUsedOnValidRequest()
    {
        $request = Request::create('test', 'POST', [
            DropZoneUploadHandler::CHUNK_UUID_INDEX => 'test',
            DropZoneUploadHandler::CHUNK_INDEX => '1',
            DropZoneUploadHandler::CHUNK_TOTAL_INDEX => '2',
        ], [], [], []);
        $this->assertTrue(DropZoneUploadHandler::canBeUsedForRequest($request));
    }

    public function testValidChunkRequest()
    {
        $request = Request::create('test', 'POST', [
            DropZoneUploadHandler::CHUNK_UUID_INDEX => 'test',
            DropZoneUploadHandler::CHUNK_INDEX => '0',
            DropZoneUploadHandler::CHUNK_TOTAL_INDEX => '2',
        ], [], [], []);

        $contentRange = new DropZoneUploadHandler($request, $this->file, new FileConfig());

        $this->assertEquals(2, $contentRange->getTotalChunks());
        $this->assertEquals(1, $contentRange->getCurrentChunk());
        $this->assertEquals(0, $contentRange->getPercentageDone());
        $contentRange->setPercentageDone(50);
        $this->assertEquals(50, $contentRange->getPercentageDone());
        $this->assertTrue($contentRange->isChunkedUpload());
        $this->assertTrue($contentRange->isFirstChunk());
        $this->assertFalse($contentRange->isLastChunk());
    }

    public function testValidChunkFinishRequest()
    {
        $request = Request::create('test', 'POST', [
            DropZoneUploadHandler::CHUNK_UUID_INDEX => 'test',
            DropZoneUploadHandler::CHUNK_INDEX => '1',
            DropZoneUploadHandler::CHUNK_TOTAL_INDEX => '2',
        ], [], [], []);

        $contentRange = new DropZoneUploadHandler($request, $this->file, new FileConfig());

        $this->assertEquals(2, $contentRange->getTotalChunks());
        $this->assertEquals(2, $contentRange->getCurrentChunk());
        $this->assertEquals(0, $contentRange->getPercentageDone());
        $contentRange->setPercentageDone(100);
        $this->assertEquals(100, $contentRange->getPercentageDone());
        $this->assertTrue($contentRange->isChunkedUpload());
        $this->assertFalse($contentRange->isFirstChunk());
        $this->assertTrue($contentRange->isLastChunk());
    }
}
