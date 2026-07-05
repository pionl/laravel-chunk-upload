<?php

namespace ChunkTests\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Handler\FlowJSUploadHandler;

class FlowJSUploadHandlerTest extends TestCase
{
    protected $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = UploadedFile::fake()->create('test');
    }

    public function testInitWithoutRequestData()
    {
        $request = Request::create('test', 'POST', [], [], [], []);

        $handler = new FlowJSUploadHandler($request, $this->file, new FileConfig());

        $this->assertNull($handler->getTotalChunks());
        $this->assertNull($handler->getCurrentChunk());
        $this->assertFalse($handler->isChunkedUpload());
    }

    public function testCanBeUsedForInvalidRequest()
    {
        $request = Request::create('test', 'POST', [], [], [], []);
        $this->assertFalse(FlowJSUploadHandler::canBeUsedForRequest($request));
    }

    public function testCanBeUsedForPartiallyFilledRequest()
    {
        $request = Request::create('test', 'POST', [
            FlowJSUploadHandler::CHUNK_UUID_INDEX => 'test',
        ], [], [], []);
        $this->assertFalse(FlowJSUploadHandler::canBeUsedForRequest($request));
    }

    public function testCanBeUsedOnValidRequest()
    {
        $request = Request::create('test', 'POST', [
            FlowJSUploadHandler::CHUNK_UUID_INDEX => 'test',
            FlowJSUploadHandler::CHUNK_NUMBER_INDEX => '1',
            FlowJSUploadHandler::TOTAL_CHUNKS_INDEX => '2',
        ], [], [], []);
        $this->assertTrue(FlowJSUploadHandler::canBeUsedForRequest($request));
    }

    public function testValidChunkRequest()
    {
        $request = Request::create('test', 'POST', [
            FlowJSUploadHandler::CHUNK_UUID_INDEX => 'test',
            FlowJSUploadHandler::CHUNK_NUMBER_INDEX => '1',
            FlowJSUploadHandler::TOTAL_CHUNKS_INDEX => '2',
        ], [], [], []);

        $config = $this->getMockBuilder(FileConfig::class)
            ->onlyMethods([
                'chunkUseHashNameForName',
                'chunkUseSessionForName',
                'chunkUseBrowserInfoForName',
            ])
            ->getMock();
        $config->expects($this->once())
            ->method('chunkUseHashNameForName')
            ->willReturn(false);
        $config->expects($this->once())
            ->method('chunkUseSessionForName')
            ->willReturn(false);
        $config->expects($this->once())
            ->method('chunkUseBrowserInfoForName')
            ->willReturn(false);

        $handler = new FlowJSUploadHandler($request, $this->file, $config);

        $this->assertEquals(2, $handler->getTotalChunks());
        $this->assertEquals(1, $handler->getCurrentChunk());
        $this->assertEquals(0, $handler->getPercentageDone());
        $this->assertTrue($handler->isChunkedUpload());
        $this->assertTrue($handler->isFirstChunk());
        $this->assertFalse($handler->isLastChunk());
        $this->assertEquals('test-test.1.part', $handler->getChunkFileName());
    }

    public function testValidChunkFinishRequest()
    {
        $request = Request::create('test', 'POST', [
            FlowJSUploadHandler::CHUNK_UUID_INDEX => 'test',
            FlowJSUploadHandler::CHUNK_NUMBER_INDEX => '2',
            FlowJSUploadHandler::TOTAL_CHUNKS_INDEX => '2',
        ], [], [], []);

        $handler = new FlowJSUploadHandler($request, $this->file, new FileConfig());

        $this->assertEquals(2, $handler->getTotalChunks());
        $this->assertEquals(2, $handler->getCurrentChunk());
        $this->assertTrue($handler->isChunkedUpload());
        $this->assertFalse($handler->isFirstChunk());
        $this->assertTrue($handler->isLastChunk());
    }
}
