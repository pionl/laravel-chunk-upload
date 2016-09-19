<?php
namespace Pion\Laravel\ChunkUpload\Handler;

/**
 * Class SingleUploadHandler
 *
 * The dumm single upload handler as the default fallback
 *
 * @package Pion\Laravel\ChunkUpload\Handler
 */
class SingleUploadHandler extends AbstractHandler
{
    /**
     * Returns the chunk file name for a storing the tmp file
     *
     * @return string
     */
    public function getChunkFileName()
    {
        return null; // never used
    }

    /**
     * Checks if the request has first chunk
     *
     * @return bool
     */
    public function isFirstChunk()
    {
        return true;
    }

    /**
     * Checks if the current request has the last chunk
     *
     * @return bool
     */
    public function isLastChunk()
    {
        return true;
    }

    /**
     * Checks if the current request is chunked upload
     *
     * @return bool
     */
    public function isChunkedUpload()
    {
        return false; // force the `SingleSave` instance
    }

    /**
     * Returns the percentage of the upload file
     *
     * @return int
     */
    public function getPercentageDone()
    {
        return 100;
    }


}