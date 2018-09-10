<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Save\SingleSave;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

/**
 * Class SingleUploadHandler.
 *
 * The simple single upload handler as the default fallback
 */
class SingleUploadHandler extends AbstractHandler
{
    /**
     * Returns the chunks ave instance for saving.
     *
     * @param ChunkStorage   $chunkStorage the chunk storage
     * @param AbstractConfig $config       the config manager
     *
     * @return SingleSave
     */
    public function startSaving($chunkStorage)
    {
        return new SingleSave($this->file, $this, $this->config);
    }

    /**
     * Returns the chunk file name for a storing the tmp file.
     *
     * @return string
     */
    public function getChunkFileName()
    {
        return null; // never used
    }

    /**
     * Checks if the request has first chunk.
     *
     * @return bool
     */
    public function isFirstChunk()
    {
        return true;
    }

    /**
     * Checks if the current request has the last chunk.
     *
     * @return bool
     */
    public function isLastChunk()
    {
        return true;
    }

    /**
     * Checks if the current request is chunked upload.
     *
     * @return bool
     */
    public function isChunkedUpload()
    {
        return false; // force the `SingleSave` instance
    }

    /**
     * Returns the percentage of the upload file.
     *
     * @return int
     */
    public function getPercentageDone()
    {
        return 100;
    }
}
