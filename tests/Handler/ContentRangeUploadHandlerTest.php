<?php

namespace ChunkTests\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Exceptions\ContentRangeValueToLargeException;
use Pion\Laravel\ChunkUpload\Handler\ContentRangeUploadHandler;

class ContentRangeUploadHandlerTest extends TestCase
{
    public function testInitWithoutBytesRange()
    {
        $request = Request::create('test', 'POST', [], [], [], []);
        $file = UploadedFile::fake();

        $contentRange = new ContentRangeUploadHandler($request, $file, new FileConfig());

        $this->assertEquals(0, $contentRange->getBytesEnd());
        $this->assertEquals(0, $contentRange->getBytesStart());
        $this->assertEquals(0, $contentRange->getBytesTotal());
        $this->assertFalse($contentRange->isChunkedUpload());
    }

    public function testPercentageDoneWithoutBytesRange()
    {
        $request = Request::create('test', 'POST', [], [], [], []);
        $file = UploadedFile::fake();

        $contentRange = new ContentRangeUploadHandler($request, $file, new FileConfig());

        $this->assertEquals(0, $contentRange->getPercentageDone());
    }

    public function testValidContentRangeFirstChunk()
    {
        $request = Request::create('test', 'POST', [], [], [], [
            'HTTP_CONTENT_RANGE' => 'bytes 0-100/1200',
        ]);
        $file = UploadedFile::fake()->create('test');

        $contentRange = new ContentRangeUploadHandler($request, $file, new FileConfig());

        $this->assertEquals(0, $contentRange->getBytesStart());
        $this->assertEquals(100, $contentRange->getBytesEnd());
        $this->assertEquals(1200, $contentRange->getBytesTotal());
        $this->assertEquals(9, $contentRange->getPercentageDone());
        $this->assertTrue($contentRange->isChunkedUpload());
        $this->assertTrue($contentRange->isFirstChunk());
    }

    public function testValidContentRangeNextChunk()
    {
        $request = Request::create('test', 'POST', [], [], [], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-100/1200',
        ]);
        $file = UploadedFile::fake();

        $contentRange = new ContentRangeUploadHandler($request, $file, new FileConfig());

        $this->assertEquals(100, $contentRange->getBytesStart());
        $this->assertEquals(100, $contentRange->getBytesEnd());
        $this->assertEquals(1200, $contentRange->getBytesTotal());
        $this->assertEquals(9, $contentRange->getPercentageDone());
        $this->assertTrue($contentRange->isChunkedUpload());
        $this->assertFalse($contentRange->isLastChunk());
        $this->assertFalse($contentRange->isFirstChunk());
    }

    public function testIsLastChunk()
    {
        $request = Request::create('test', 'POST', [], [], [], [
            'HTTP_CONTENT_RANGE' => 'bytes 1100-1199/1200',
        ]);
        $file = UploadedFile::fake();

        $contentRange = new ContentRangeUploadHandler($request, $file, new FileConfig());

        $this->assertEquals(1199, $contentRange->getBytesEnd());
        $this->assertEquals(1100, $contentRange->getBytesStart());
        $this->assertEquals(1200, $contentRange->getBytesTotal());
        $this->assertEquals(100, $contentRange->getPercentageDone());
        $this->assertTrue($contentRange->isLastChunk());
        $this->assertFalse($contentRange->isFirstChunk());
    }

    /**
     * Checks if canBeUsedForRequest returns false when content-range is missing.
     */
    public function testCanBeUsedForInvalidRequest()
    {
        $request = Request::create('test', 'POST', [], [], [], []);
        $this->assertFalse(ContentRangeUploadHandler::canBeUsedForRequest($request));
    }

    /**
     * Checks if canBeUsedForRequest returns false when content-range is invalid.
     */
    public function testCanBeUsedForInvalidContentRangeFormat()
    {
        $request = Request::create('test', 'POST', [], [], [], [
            'HTTP_CONTENT_RANGE' => 'bytes ss-ss',
        ]);
        $this->assertFalse(ContentRangeUploadHandler::canBeUsedForRequest($request));
    }

    /**
     * Checks if canBeUsedForRequest returns false when content-range is missing.
     */
    public function testCanBeUsedForValidRange()
    {
        $request = Request::create('test', 'POST', [], [], [], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-100/1000',
        ]);
        $this->assertTrue(ContentRangeUploadHandler::canBeUsedForRequest($request));
    }

    /**
     * Test the maximum float value.
     */
    public function testMaxFloatValue()
    {
        $maxFloat = '18';
        for ($i = 0; $i < 309; ++$i) {
            $maxFloat .= '0';
        }
        $request = Request::create('test', 'POST', [], [], [], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-100/'.$maxFloat,
        ]);
        $file = UploadedFile::fake();

        $this->expectException(ContentRangeValueToLargeException::class);
        $this->expectExceptionMessage('The content range value is to large');
        $this->expectExceptionCode(500);
        new ContentRangeUploadHandler($request, $file, new FileConfig());
    }

    public function testMaxInt()
    {
        $request = Request::create('test', 'POST', [], [], [], [
            'HTTP_CONTENT_RANGE' => 'bytes 100-100/2147483648',
        ]);
        $file = UploadedFile::fake();

        $contentRange = new ContentRangeUploadHandler($request, $file, new FileConfig());

        $this->assertEquals(100, $contentRange->getBytesStart());
        $this->assertEquals(100, $contentRange->getBytesEnd());
        $this->assertEquals(2147483648, $contentRange->getBytesTotal());
    }
}
