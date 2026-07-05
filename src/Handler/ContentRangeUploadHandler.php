<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;
use Pion\Laravel\ChunkUpload\Exceptions\ContentRangeValueToLargeException;
use Pion\Laravel\ChunkUpload\Save\ChunkSave;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

/**
 * Upload receiver that detects the content range by the header value and form data
 * - content-disposition attachment; filename="6804117-uhd_4096_2160_25fps.mp4"
 * - content-type multipart/form-data; boundary=----WebKitFormBoundaryJjUYyYYal4tJlBrz
 * - content-length 1000337
 * - content-range bytes 63000000-63999999/68402438.
 *
 * Works with:
 * - blueimp-file-upload - partial support (simple chunked and single upload)
 *   https://github.com/blueimp/jQuery-File-Upload
 */
class ContentRangeUploadHandler extends AbstractHandler
{
    /**
     * The index for the header.
     */
    public const CONTENT_RANGE_INDEX = 'content-range';

    /**
     * Determines if the upload is via chunked upload.
     *
     * @var bool
     */
    protected $chunkedUpload = false;

    /**
     * Current chunk start bytes.
     *
     * @var int
     */
    protected $bytesStart = 0;

    /**
     * Current chunk bytes end.
     *
     * @var int
     */
    protected $bytesEnd = 0;

    /**
     * The files total bytes.
     *
     * @var int
     */
    protected $bytesTotal = 0;

    /**
     * AbstractReceiver constructor.
     *
     * @param Request        $request
     * @param UploadedFile   $file
     * @param AbstractConfig $config
     *
     * @throws ContentRangeValueToLargeException
     */
    public function __construct(Request $request, $file, $config)
    {
        parent::__construct($request, $file, $config);

        $contentRange = $this->request->header(self::CONTENT_RANGE_INDEX, '');

        $this->tryToParseContentRange($contentRange);
    }

    /**
     * Checks if the current abstract handler can be used via HandlerFactory.
     *
     * @param Request $request
     *
     * @return bool
     *
     * @throws ContentRangeValueToLargeException
     */
    public static function canBeUsedForRequest(Request $request)
    {
        return (new static($request, null, null))->isChunkedUpload();
    }

    /**
     * Returns the chunk save instance for saving.
     *
     * @param ChunkStorage $chunkStorage the chunk storage
     *
     * @return ChunkSave
     *
     * @throws ChunkSaveException
     */
    public function startSaving($chunkStorage)
    {
        return new ChunkSave($this->file, $this, $chunkStorage, $this->config);
    }

    /**
     * Tries to parse the content range from the string.
     *
     * @param string $contentRange
     *
     * @throws ContentRangeValueToLargeException
     */
    protected function tryToParseContentRange($contentRange)
    {
        // try to get the content range
        if (preg_match("/bytes ([\d]+)-([\d]+)\/([\d]+)/", $contentRange, $matches)) {
            $this->chunkedUpload = true;

            // write the bytes values
            $this->bytesStart = $this->convertToNumericValue($matches[1]);
            $this->bytesEnd = $this->convertToNumericValue($matches[2]);
            $this->bytesTotal = $this->convertToNumericValue($matches[3]);
        }
    }

    /**
     * Converts the string value to float - throws exception if float value is exceeded.
     *
     * @param string $value
     *
     * @return float
     *
     * @throws ContentRangeValueToLargeException
     */
    protected function convertToNumericValue($value)
    {
        $floatVal = floatval($value);

        if (INF === $floatVal) {
            throw new ContentRangeValueToLargeException();
        }

        return $floatVal;
    }

    /**
     * Returns the first chunk.
     *
     * @return bool
     */
    public function isFirstChunk()
    {
        return 0 == $this->bytesStart;
    }

    /**
     * Returns the chunks count.
     *
     * @return int
     */
    public function isLastChunk()
    {
        // the bytes starts from zero, remove 1 byte from total
        return $this->bytesEnd >= ($this->bytesTotal - 1);
    }

    /**
     * Returns the current chunk index.
     *
     * @return bool
     */
    public function isChunkedUpload()
    {
        return $this->chunkedUpload;
    }

    /**
     * @return int returns the starting bytes for current request
     */
    public function getBytesStart()
    {
        return $this->bytesStart;
    }

    /**
     * @return int returns the ending bytes for current request
     */
    public function getBytesEnd()
    {
        return $this->bytesEnd;
    }

    /**
     * @return int returns the total bytes for the file
     */
    public function getBytesTotal()
    {
        return $this->bytesTotal;
    }

    /**
     * Returns the chunk file name. Uses the original client name and the total bytes.
     *
     * @return string returns the original name with the part extension
     *
     * @see createChunkFileName()
     */
    public function getChunkFileName()
    {
        return $this->createChunkFileName('cr', $this->bytesTotal);
    }

    /**
     * @return int
     */
    public function getPercentageDone()
    {
        // Check that we have received total bytes
        if (0 == $this->getBytesTotal()) {
            return 0;
        }

        return ceil($this->getBytesEnd() / $this->getBytesTotal() * 100);
    }

    public function requiresFinalChunkOnLastChunk(): bool
    {
        return true;
    }
}
