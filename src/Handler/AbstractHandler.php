<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
     * Returns the chunk file name
     *
     * @return string
     */
    abstract public function getChunkFileName();

    /**
     * Returns the first chunk
     * @return bool
     */
    abstract public function isFirstChunk();

    /**
     * Returns the chunks count
     *
     * @return int
     */
    abstract public function isLastChunk();

    /**
     * Returns the current chunk index
     *
     * @return bool
     */
    abstract public function isChunkedUpload();
}