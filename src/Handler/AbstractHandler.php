<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;
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
     * @var AbstractConfig
     */
    protected $config;

    /**
     * AbstractReceiver constructor.
     *
     * @param Request        $request
     * @param UploadedFile   $file
     * @param AbstractConfig $config
     */
    public function __construct(Request $request, $file, $config)
    {
        $this->request = $request;
        $this->file = $file;
        $this->config = $config;
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
        return false;
    }

    /**
     * Builds the chunk file name per session and the original name. You can
     * provide custom additional name at the end of the generated file name. All chunk
     * files has .part extension
     *
     * @param string|null $additionalName
     *
     * @return string
     *
     * @see UploadedFile::getClientOriginalName()
     * @see Session::getId()
     */
    protected function createChunkFileName($additionalName = null)
    {
        // prepare basic name structure
        $array = [
            $this->file->getClientOriginalName()
        ];

        // ensure that the chunk name is for unique for the client session

        // the session needs more config on the provider
        if ($this->config->chunkUseSessionForName()) {
            $array[] = Session::getId();
        }

        // can work without any additional setup
        if ($this->config->chunkUseBrowserInfoForName()) {
            $array[] = md5($this->request->ip().$this->request->header("User-Agent", "no-browser"));
        }

        // add
        if (!is_null($additionalName)) {
            $array[] = $additionalName;
        }


        // build name
        return implode("-", $array).".".ChunkStorage::CHUNK_EXTENSION;
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

    /**
     * Returns the percentage of the upload file
     *
     * @return int
     */
    abstract public function getPercentageDone();
}