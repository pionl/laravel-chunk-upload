<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Handler\Traits\HandleParallelUploadTrait;

class ResumableJSUploadHandler extends ChunksInRequestUploadHandler
{
    use HandleParallelUploadTrait;

    const CHUNK_UUID_INDEX = 'resumableIdentifier';
    const CHUNK_NUMBER_INDEX = 'resumableChunkNumber';
    const TOTAL_CHUNKS_INDEX = 'resumableTotalChunks';

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
     * @param UploadedFile   $file
     * @param AbstractConfig $config
     */
    public function __construct(Request $request, $file, $config)
    {
        parent::__construct($request, $file, $config);

        $this->fileUuid = $request->get(self::CHUNK_UUID_INDEX);
    }

    /**
     * Append the resumable file - uuid and pass the current chunk index for parallel upload.
     *
     * @return string
     */
    public function getChunkFileName()
    {
        return $this->createChunkFileName(substr($this->fileUuid,0,40), $this->getCurrentChunk());
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
        return $request->get(self::CHUNK_NUMBER_INDEX);
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
        return $request->get(self::TOTAL_CHUNKS_INDEX);
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
        return $request->has(self::CHUNK_NUMBER_INDEX) && $request->has(self::TOTAL_CHUNKS_INDEX) &&
            $request->has(self::CHUNK_UUID_INDEX);
    }
}
