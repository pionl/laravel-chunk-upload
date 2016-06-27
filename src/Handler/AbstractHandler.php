<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Session;

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
     * Builds the chunk file name per session and the original name. You can
     * provide custom aditional name at the end of the generated file name.
     *
     * @param string|null $aditionalName
     *
     * @return string
     *
     * @see UploadedFile::getClientOriginalName()
     * @see Session::getId()
     */
    protected function createChunkFileName($aditionalName = null)
    {
        // prepare basic name structure
        $array = [
            $this->file->getClientOriginalName(),
            Session::getId(),
        ];

        // add
        if (!is_null($aditionalName)) {
            $array[] = $aditionalName;
        }

        // build name
        return implode("-", $array);
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