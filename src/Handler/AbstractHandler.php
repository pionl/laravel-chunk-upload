<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * The handler that will detect if we can continue the chunked upload
 * 
 * @package Pion\Laravel\ChunkUpload\Handler
 */
abstract class AbstractHandler
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * AbstractReceiver constructor.
     *
     * @param Request      $request
     * @param UploadedFile $file
     */
    public function __construct(Request  $request, UploadedFile $file)
    {
        $this->request = $request;
        $this->file = $file;
    }

    /**
     * Returns the chunk file name for a storing the tmp file
     *
     * @return string
     */
    abstract public function getChunkFileName();

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
}