<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Save\AbstractSave;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

/**
 * The handler that will detect if we can continue the chunked upload
 *
 * @package Pion\Laravel\ChunkUpload\Handler
 */
abstract class AbstractUploadHandler extends AbstractHandler
{
    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * AbstractReceiver constructor.
     *
     * @param Request        $request
     * @param UploadedFile   $file
     * @param AbstractConfig $config
     */
    public function __construct(Request $request, $file, $config)
    {
        $filename = (null === $file) ? null : $file->getClientOriginalName();
        parent::__construct($request, $filename, $config);

        $this->file = $file;
    }

    /**
     * Creates save instance and starts saving the uploaded file
     *
     * @param ChunkStorage    $chunkStorage the chunk storage
     *
     * @return AbstractSave
     */
    abstract public function startSaving($chunkStorage);

    /**
     * Checks if the request has first chunk
     *
     * @return bool
     */
    abstract public function isFirstChunk();

    /**
     * Checks if the current request has the last chunk
     *
     * @return bool
     */
    abstract public function isLastChunk();

    /**
     * Checks if the current request is chunked upload
     *
     * @return bool
     */
    abstract public function isChunkedUpload();

    /**
     * Returns the percentage of the upload file
     *
     * @return int
     */
    abstract public function getPercentageDone();
}
