<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;

/**
 * Class NgFileUploadHandler
 *
 * Upload receiver that detects the content range from he request value - chunks
 * Works with:
 * - ng-file-upload: https://github.com/danialfarid/ng-file-upload
 *
 * @package Pion\Laravel\ChunkUpload\Handler
 */
class NgFileUploadHandler extends ChunksInRequestUploadHandler
{

    /**
     * Key for number of sending chunk
     *
     * @static string
     */
    const KEY_CHUNK_NUMBER = '_chunkNumber';

    /**
     * Key for total size of all chunks
     *
     * @static string
     */
    const KEY_TOTAL_SIZE = '_totalSize';

    /**
     * Key for every chunk size
     *
     * @static string
     */
    const KEY_CHUNK_SIZE = '_chunkSize';

    /**
     * Key for current chunk size
     *
     * @static string
     */
    const KEY_CHUNK_CURRENT_SIZE = '_currentChunkSize';

    /**
     * Checks if the current abstract handler can be used via HandlerFactory
     *
     * @param Request $request
     *
     * @return bool
     */
    public static function canBeUsedForRequest(Request $request)
    {
        return $request->has(static::KEY_CHUNK_NUMBER)
               && $request->has(static::KEY_TOTAL_SIZE)
               && $request->has(static::KEY_CHUNK_SIZE)
               && $request->has(static::KEY_CHUNK_CURRENT_SIZE);
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
        return intval(
            ceil($request->get(static::KEY_TOTAL_SIZE) / $request->get(static::KEY_CHUNK_SIZE))
        );
    }
}
