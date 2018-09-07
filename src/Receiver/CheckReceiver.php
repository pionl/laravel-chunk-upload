<?php
namespace Pion\Laravel\ChunkUpload\Receiver;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Handler\AbstractCheckHandler;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class CheckReceiver
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * The handler that detects what upload process is being used
     *
     * @var AbstractCheckHandler
     */
    protected $handler = null;

    /**
     * The chunk storage
     *
     * @var ChunkStorage
     */
    protected $chunkStorage;

    /**
     * The current config
     * @var AbstractConfig
     */
    protected $config;

    /**
     * The file receiver for the given file index
     *
     * @param Request             $request         the current request
     * @param string              $handlerClass    the handler class name for detecting the file upload
     * @param ChunkStorage|null   $chunkStorage    the chunk storage, on null will use the instance from app container
     * @param AbstractConfig|null $config          the config, on null will use the instance from app container
     */
    public function __construct(Request $request, $handlerClass, $chunkStorage = null, $config = null)
    {
        $this->request = $request;
        $this->chunkStorage = is_null($chunkStorage) ? ChunkStorage::storage() : $chunkStorage;
        $this->config = is_null($config) ? AbstractConfig::config() : $config;

        $this->handler = new $handlerClass($this->request, $this->config);
    }

    /**
     * Tries to check if the requested file or chunk is already uploaded.
     *
     * Returns false if there is no handler which supports the request or the handler does not supports the request.
     *
     * Otherwise returns an array which should be sent to the client as a JSON response.
     *
     * @return bool|array
     */
    public function check()
    {
        if (is_object($this->handler) === false) {
            return false;
        }

        return $this->handler->check($this->chunkStorage);
    }
}
