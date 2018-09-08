<?php
/**
 *
 */

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

/**
 * Class ResumableJSCheckHandler
 *
 * Check handler that tells if the requested chunk is already uploaded or not
 *
 * Works with:
 * - simple-uploader: https://github.com/simple-uploader/Uploader
 *
 * @package Pion\Laravel\ChunkUpload\Handler
 */
class ChunkCheckHandler extends AbstractCheckHandler
{
    const CHUNK_UUID_INDEX = 'identifier';

    const CHUNK_NUMBER_INDEX = 'chunkNumber';

    const FILENAME_INDEX = 'filename';

    /**
     * The current chunk progress
     *
     * @var int
     */
    protected $currentChunk = 0;

    /**
     * The Resumable file uuid for unique chunk upload session.
     *
     * @var string|null
     */
    protected $fileUuid = null;

    /**
     * AbstractReceiver constructor.
     *
     * @param Request        $request
     * @param AbstractConfig $config
     */
    public function __construct(Request $request, $config)
    {
        parent::__construct($request, $config);

        $this->currentChunk = intval($request->get(static::CHUNK_NUMBER_INDEX)) + 1;
        $this->fileUuid = $request->get(static::CHUNK_UUID_INDEX);
    }

    /**
     * Returns the filename from the request
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getFilenameFromRequest(Request $request)
    {
        return $request->get(static::FILENAME_INDEX);
    }

    /**
     * Returns the chunk file name for a storing the tmp file
     *
     * @return string
     */
    public function getChunkFileName()
    {
        return $this->createChunkFileName($this->fileUuid, $this->currentChunk);
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
        return $request->has(static::CHUNK_UUID_INDEX, static::CHUNK_NUMBER_INDEX, static::FILENAME_INDEX);
    }

    /**
     * Checks if the target file or chunk is already uploaded
     *
     * @param ChunkStorage $chunkStorage
     * @return false|array
     */
    public function check($chunkStorage)
    {
        $fullFilePath = $this->getFullFilePath($chunkStorage);

        if(!\File::exists($fullFilePath)) {
            return false;
        }

        return [];
    }
}