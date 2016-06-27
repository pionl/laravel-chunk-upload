<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class JqueryUploadReceiver
 *
 * Upload receiver that detects the content range by the the header value.
 *
 * @package Pion\Laravel\ChunkUpload\Handler
 */
class ContentRangeUploadHandler extends AbstractHandler
{
    /**
     * The index for the header
     */
    const CONTENT_RANGE_INDEX = "content-range";

    /**
     * Determines if the upload is via chunked upload
     * @var bool
     */
    protected $chunkedUpload = false;

    /**
     * Current chunk start bytes
     * @var int
     */
    protected $bytesStart = 0;

    /**
     * Current chunk bytes end
     * @var int
     */
    protected $bytesEnd = 0;

    /**
     * The files total bytes
     * @var int
     */
    protected $bytesTotal = 0;

    /**
     * AbstractReceiver constructor.
     *
     * @param Request      $request
     * @param UploadedFile $file
     */
    public function __construct(Request $request, UploadedFile $file)
    {
        parent::__construct($request, $file);

        $contentRange = $this->request->header(self::CONTENT_RANGE_INDEX);

        $this->tryToParseContentRange($contentRange);
    }

    /**
     * Tries to parse the content range from the string
     *
     * @param string $contentRange
     */
    protected function tryToParseContentRange($contentRange)
    {
        // try to get the content range
        if (preg_match("/bytes ([\d]+)-([\d]+)\/([\d]+)/", $contentRange, $matches)) {

            $this->chunkedUpload = true;

            // write the bytes values
            $this->bytesStart = intval($matches[1]);
            $this->bytesEnd = intval($matches[2]);
            $this->bytesTotal = intval($matches[3]);
        }
    }

    /**
     * Returns the first chunk
     * @return bool
     */
    public function isFirstChunk()
    {
        return $this->bytesStart == 0;
    }

    /**
     * Returns the chunks count
     *
     * @return int
     */
    public function isLastChunk()
    {
        // the bytes starts from zero, remove 1 byte from total
        return $this->bytesEnd >= ($this->bytesTotal - 1);
    }

    /**
     * Returns the current chunk index
     *
     * @return bool
     */
    public function isChunkedUpload()
    {
        return $this->chunkedUpload;
    }

    /**
     * @return int
     */
    public function getBytesStart()
    {
        return $this->bytesStart;
    }

    /**
     * @return int
     */
    public function getBytesEnd()
    {
        return $this->bytesEnd;
    }

    /**
     * @return int
     */
    public function getBytesTotal()
    {
        return $this->bytesTotal;
    }

    /**
     * Returns the chunk file name
     *
     * @return string
     */
    public function getChunkFileName()
    {
        return $this->file->getClientOriginalName()."-".$this->bytesTotal.".part";
    }
    
}