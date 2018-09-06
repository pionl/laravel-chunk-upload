<?php
namespace Pion\Laravel\ChunkUpload\Receiver;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
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
     * @param string|UploadedFile $fileIndexOrFile the desired file index to use in request or the final UploadedFile
     * @param Request             $request         the current request
     * @param string              $handlerClass    the handler class name for detecting the file upload
     * @param ChunkStorage|null   $chunkStorage    the chunk storage, on null will use the instance from app container
     * @param AbstractConfig|null $config          the config, on null will use the instance from app container
     *
     * @throws UploadFailedException
     */
    public function __construct(Request $request, $handlerClass, $chunkStorage = null, $config = null)
    {
        $this->request = $request;
        $this->chunkStorage = is_null($chunkStorage) ? ChunkStorage::storage() : $chunkStorage;
        $this->config = is_null($config) ? AbstractConfig::config() : $config;

        $this->handler = new $handlerClass($this->request, $this->config);
    }

    /**
     * Tries to handle the upload request. If the file is not uploaded, returns false. If the file
     * is present in the request, it will create the save object.
     *
     * If the file in the request is chunk, it will create the `ChunkSave` object, otherwise creates the `SingleSave`
     * which doesn't nothing at this moment.
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
