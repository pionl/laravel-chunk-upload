<?php

namespace ChunkTests\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Handler\DropZoneUploadHandler;
use Pion\Laravel\ChunkUpload\Handler\FilePondUploadHandler;

class FilePondUploadHandlerTest extends TestCase
{
    protected $file = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = UploadedFile::fake()->create('test');
    }

    /**
     * Checks if canBeUsedForRequest returns false when data is missing.
     */
    public function testCanBeUsedForInvalidRequest()
    {
        $request = Request::create('test', 'POST', [], [], [], []);
        $this->assertFalse(FilePondUploadHandler::canBeUsedForRequest($request));
    }

    /**
     * FilePond initiates a chunked upload by sending an empty request with an 'Upload-Length'
     * header, and just looks for a server ID as response. We don't use our handler for this.
     */
    public function testIsNotUsedForInitialRequest()
    {
        $request = Request::create('test', 'POST', [], [], [], [
            'HTTP_UPLOAD_LENGTH' => 500
        ]);

        $this->assertFalse(FilePondUploadHandler::canBeUsedForRequest($request));
    }

    public function testCanBeUsedOnValidRequest()
    {
        $request = Request::create('test', 'PATCH', [], [], [], [
            'HTTP_UPLOAD_LENGTH' => 500,
            'HTTP_UPLOAD_OFFSET' => 0,
            'HTTP_UPLOAD_NAME'   => 'test.pdf'
        ]);

        $this->assertTrue(FilePondUploadHandler::canBeUsedForRequest($request));
    }

    public function testValidChunkRequest()
    {
        $request = Request::create('test', 'PATCH', [], [], [], [
            'HTTP_UPLOAD_LENGTH' => 500,
            'HTTP_UPLOAD_OFFSET' => 0,
            'HTTP_UPLOAD_NAME'   => 'test.pdf'
        ]);

        $handler = new FilePondUploadHandler($request, $this->file, new FileConfig());

        $this->assertTrue($handler->isFirstChunk());
        $this->assertEquals(0, $handler->getPercentageDone());
        $this->assertFalse($handler->isLastChunk());
    }
}
