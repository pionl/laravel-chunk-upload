<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;

/**
 * Class ChunksInRequestUploadHandler
 *
 * Upload receiver that detects the content range fromt he request value - chunks
 * Works with:
 * - PUpload: https://github.com/moxiecode/plupload/
 *
 * @package Pion\Laravel\ChunkUpload\Handler
 */
class ChunksInRequestUploadHandler extends AbstractHandler
{
    /**
     * Determines if the upload is via chunked upload
     * @var bool
     */
    protected $chunkedUpload = false;

    /**
     * The current chunk progress
     * @var int
     */
    protected $currentChunk = 0;

    /**
     * The total of chunks
     * @var int
     */
    protected $chunksTotal = 0;


    /**
     * AbstractReceiver constructor.
     *
     * @param Request        $request
     * @param UploadedFile   $file
     * @param AbstractConfig $config
     */
    public function __construct(Request $request, $file, $config)
    {
        parent::__construct($request, $file, $config);

        // the chunk is indexed from zero (for 5 chunks: 0,1,2,3,4)
        $this->currentChunk = $request->get("chunk") + 1;
        $this->chunksTotal = $request->get("chunks");
    }

    /**
     * Checks if the current abstract handler can be used via HandlerFactory
     *
     * @param Request $request
     *
     * @return bool
     */
    public static function canBeUsedForRequest(Request $request)
    {
        return $request->has("chunk") && $request->has("chunks");
    }

    /**
     * Returns the first chunk
     * @return bool
     */
    public function isFirstChunk()
    {
        return $this->currentChunk == 1;
    }

    /**
     * Returns the chunks count
     *
     * @return int
     */
    public function isLastChunk()
    {
        // the bytes starts from zero, remove 1 byte from total
        return $this->currentChunk == $this->chunksTotal;
    }

    /**
     * Returns the current chunk index
     *
     * @return bool
     */
    public function isChunkedUpload()
    {
        return $this->chunksTotal > 1;
    }

    /**
     * Returns the chunk file name. Uses the original client name and the total bytes
     *
     * @return string returns the original name with the part extension
     *
     * @see createChunkFileName()
     */
    public function getChunkFileName()
    {
        return $this->createChunkFileName($this->chunksTotal);
    }

    /**
     * @return int
     */
    public function getTotalChunks()
    {
        return $this->chunksTotal;
    }

    /**
     * @return int
     */
    public function getCurrentChunk()
    {
        return $this->currentChunk;
    }

    /**
     * Returns the percentage of the uploaded file
     * @return int
     */
    public function getPercentageDone()
    {
        return ceil($this->currentChunk / $this->chunksTotal * 100);
    }
}