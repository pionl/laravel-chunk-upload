<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;

class ResumableJSUploadHandler extends ChunksInRequestUploadHandler
{
    const CHUNK_NUMBER_INDEX = 'resumableChunkNumber';
    const TOTAL_CHUNKS_INDEX = 'resumableTotalChunks';

    /**
     * Returns current chunk from the request
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
     * Returns current chunk from the request
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
     * Checks if the current abstract handler can be used via HandlerFactory
     *
     * @param Request $request
     *
     * @return bool
     */
    public static function canBeUsedForRequest(Request $request)
    {
        return $request->has(self::CHUNK_NUMBER_INDEX) && $request->has(self::TOTAL_CHUNKS_INDEX);
    }

}