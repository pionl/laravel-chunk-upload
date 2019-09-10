<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Exceptions\ChunkInvalidValueException;

/**
 * Class NgFileUploadHandler.
 *
 * Upload receiver that detects the content range from he request value - chunks
 * Works with:
 * - ng-file-upload: https://github.com/danialfarid/ng-file-upload
 */
class NgFileUploadHandler extends ChunksInRequestUploadHandler
{
    /**
     * Key for number of sending chunk.
     *
     * @static string
     */
    const KEY_CHUNK_NUMBER = '_chunkNumber';

    /**
     * Key for total size of all chunks.
     *
     * @static string
     */
    const KEY_TOTAL_SIZE = '_totalSize';

    /**
     * Key for every chunk size.
     *
     * @static string
     */
    const KEY_CHUNK_SIZE = '_chunkSize';

    /**
     * Key for current chunk size.
     *
     * @static string
     */
    const KEY_CHUNK_CURRENT_SIZE = '_currentChunkSize';

    /**
     * Checks if the current handler can be used via HandlerFactory.
     *
     * @param Request $request
     *
     * @return bool
     *
     * @throws ChunkInvalidValueException
     */
    public static function canBeUsedForRequest(Request $request)
    {
        $hasChunkParams = $request->has(static::KEY_CHUNK_NUMBER)
                          && $request->has(static::KEY_TOTAL_SIZE)
                          && $request->has(static::KEY_CHUNK_SIZE)
                          && $request->has(static::KEY_CHUNK_CURRENT_SIZE);

        return $hasChunkParams && self::checkChunkParams($request);
    }

    /**
     * @return int
     */
    public function getPercentageDone()
    {
        // Check that we have received total chunks
        if (!$this->chunksTotal) {
            return 0;
        }

        return intval(parent::getPercentageDone());
    }

    /**
     * @param Request $request
     *
     * @return bool
     *
     * @throws ChunkInvalidValueException
     */
    protected static function checkChunkParams($request)
    {
        $isInteger = ctype_digit($request->input(static::KEY_CHUNK_NUMBER))
                     && ctype_digit($request->input(static::KEY_TOTAL_SIZE))
                     && ctype_digit($request->input(static::KEY_CHUNK_SIZE))
                     && ctype_digit($request->input(static::KEY_CHUNK_CURRENT_SIZE));

        if ($request->get(static::KEY_CHUNK_SIZE) < $request->get(static::KEY_CHUNK_CURRENT_SIZE)) {
            throw new ChunkInvalidValueException();
        }

        if ($request->get(static::KEY_CHUNK_NUMBER) < 0) {
            throw new ChunkInvalidValueException();
        }

        if ($request->get(static::KEY_TOTAL_SIZE) < 0) {
            throw new ChunkInvalidValueException();
        }

        return $isInteger;
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
        if (!$request->get(static::KEY_CHUNK_SIZE)) {
            return 0;
        }

        return intval(
            ceil($request->get(static::KEY_TOTAL_SIZE) / $request->get(static::KEY_CHUNK_SIZE))
        );
    }
}
