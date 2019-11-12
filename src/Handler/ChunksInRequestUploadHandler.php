<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;
use Pion\Laravel\ChunkUpload\Save\ChunkSave;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

/**
 * Class ChunksInRequestUploadHandler.
 *
 * Upload receiver that detects the content range from he request value - chunks
 * Works with:
 * - PUpload: https://github.com/moxiecode/plupload/
 */
class ChunksInRequestUploadHandler extends AbstractHandler
{
    /**
     * Key for number of sending chunk.
     *
     * @static string
     */
    const KEY_CHUNK_NUMBER = 'chunk';

    /**
     * Key for number of all chunks.
     *
     * @static string
     */
    const KEY_ALL_CHUNKS = 'chunks';

    /**
     * The current chunk progress.
     *
     * @var int
     */
    protected $currentChunk = 0;

    /**
     * The total of chunks.
     *
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

        $this->currentChunk = $this->getCurrentChunkFromRequest($request);
        $this->chunksTotal = $this->getTotalChunksFromRequest($request);
    }

    /**
     * Checks if the current abstract handler can be used via HandlerFactory.
     *
     * @param Request $request
     *
     * @return bool
     */
    public static function canBeUsedForRequest(Request $request)
    {
        return $request->has(static::KEY_CHUNK_NUMBER) && $request->has(static::KEY_ALL_CHUNKS);
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
     * Returns current chunk from the request.
     *
     * @param Request $request
     *
     * @return int
     */
    protected function getCurrentChunkFromRequest(Request $request)
    {
        // the chunk is indexed from zero (for 5 chunks: 0,1,2,3,4)
        return intval($request->get(static::KEY_CHUNK_NUMBER)) + 1;
    }

    /**
     * Returns current chunk from the request.
     *
     * @param Request $request
     *
     * @return int
     */
    protected function getTotalChunksFromRequest(Request $request)
    {
        return intval($request->get(static::KEY_ALL_CHUNKS));
    }

    /**
     * Returns the first chunk.
     *
     * @return bool
     */
    public function isFirstChunk()
    {
        return 1 == $this->currentChunk;
    }

    /**
     * Checks if the chunk is last.
     *
     * @return bool
     */
    public function isLastChunk()
    {
        // the bytes starts from zero, remove 1 byte from total
        return $this->currentChunk == $this->chunksTotal;
    }

    /**
     * Returns the current chunk index.
     *
     * @return bool
     */
    public function isChunkedUpload()
    {
        return $this->chunksTotal > 1;
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
     * Returns the percentage of the uploaded file.
     *
     * @return int
     */
    public function getPercentageDone()
    {
        return ceil($this->currentChunk / $this->chunksTotal * 100);
    }
}
