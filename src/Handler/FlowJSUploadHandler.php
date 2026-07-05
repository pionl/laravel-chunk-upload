<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Handler\Traits\HandleParallelUploadTrait;

/**
 * Flow-js implementation that sends data in form-body:
 * - fixtures/FlowJs-body.txt
 *
 * @see https://github.com/flowjs/flow.js
 */
class FlowJSUploadHandler extends ChunksInRequestUploadHandler
{
    use HandleParallelUploadTrait;

    public const CHUNK_UUID_INDEX = 'flowIdentifier';
    public const CHUNK_NUMBER_INDEX = 'flowChunkNumber';
    public const TOTAL_CHUNKS_INDEX = 'flowTotalChunks';

    /**
     * The Resumable file uuid for unique chunk upload session.
     *
     * @var string|null
     */
    protected $fileUuid;

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

        $this->fileUuid = $request->input(self::CHUNK_UUID_INDEX);
    }

    /**
     * Append the resumable file - uuid and pass the current chunk index for parallel upload.
     *
     * @return string
     */
    public function getChunkFileName()
    {
        return $this->createChunkFileName('fjs', $this->fileUuid, $this->getCurrentChunk());
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
        return $request->input(self::CHUNK_NUMBER_INDEX);
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
        return $request->input(self::TOTAL_CHUNKS_INDEX);
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
        return $request->has(self::CHUNK_NUMBER_INDEX) && $request->has(self::TOTAL_CHUNKS_INDEX)
            && $request->has(self::CHUNK_UUID_INDEX);
    }

    public function requiresFinalChunkOnLastChunk(): bool
    {
        return true;
    }
}
