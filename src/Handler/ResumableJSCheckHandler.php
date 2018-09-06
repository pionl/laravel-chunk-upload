<?php
/**
 *
 */

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ResumableJSCheckHandler extends AbstractCheckHandler
{
    const CHUNK_UUID_INDEX = 'resumableIdentifier';

    const CHUNK_NUMBER_INDEX = 'resumableChunkNumber';

    const FILENAME_INDEX = 'resumableFilename';

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
        $this->fileUuid = $request->get(self::CHUNK_UUID_INDEX);
    }

    /**
     * Returns the filename from the request
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getFilenameFromRequest(Request $request)
    {
        return $request->get(self::FILENAME_INDEX);
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
        return $request->has(self::CHUNK_UUID_INDEX)
            && $request->has(self::CHUNK_NUMBER_INDEX)
            && $request->has(self::FILENAME_INDEX);
    }

    /**
     * Checks if the target file or chunk is already uploaded
     *
     * @param ChunkStorage $chunkStorage
     * @return false|array
     */
    public function check($chunkStorage)
    {
        $path[] = $chunkStorage->getDiskPathPrefix();
        $path[] = $chunkStorage->directory();
        $path[] = $this->getChunkFileName();

        $chunkFullFilePath = implode('', $path);

        if(!\File::exists($chunkFullFilePath)) {
            return false;
        }

        return [];
    }
}