<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;

/**
 * Class ChunksInRequestSimpleUploadHandler.
 *
 * Upload receiver that detects the content range from he request value - chunks
 * Works with:
 * - simple-uploader: https://github.com/simple-uploader
 */
class ChunksInRequestSimpleUploadHandler extends ChunksInRequestUploadHandler
{
    /**
     * Key for number of sending chunk.
     *
     * @static string
     */
    const KEY_CHUNK_NUMBER = 'chunkNumber';

    /**
     * Key for number of all chunks.
     *
     * @static string
     */
    const KEY_ALL_CHUNKS = 'totalChunks';

    /**
     * Returns current chunk from the request.
     *
     * @param Request $request
     *
     * @return int
     */
    protected function getCurrentChunkFromRequest(Request $request)
    {
        // the chunk is indexed from 1 (for 5 chunks: 1,2,3,4,5)
        return intval($request->get(static::KEY_CHUNK_NUMBER));
    }
}
