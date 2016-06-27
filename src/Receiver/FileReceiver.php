<?php
namespace Pion\Laravel\ChunkUpload\Receiver;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Save\AbstractSave;
use Pion\Laravel\ChunkUpload\Save\ChunkSave;
use Pion\Laravel\ChunkUpload\Save\SingleSave;
use Illuminate\Http\UploadedFile;

class FileReceiver
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UploadedFile|null
     */
    protected $file;

    /**
     * The handler that detects what upload proccess is beeing used
     *
     * @var AbstractHandler
     */
    protected $handler = null;

    /**
     * The file receiver for the given file index
     *
     * @param string  $fileIndex
     * @param Request $request
     * @param string  $handlerClass the handler class name for detecting the file upload
     */
    public function __construct($fileIndex, Request $request, $handlerClass)
    {
        $this->request = $request;
        $this->file = $request->file($fileIndex);

        if ($this->isUploaded()) {
            $this->handler = new $handlerClass($this->request, $this->file);
        }
    }

    /**
     * Checks if the file was uploaded
     *
     * @return bool
     */
    public function isUploaded()
    {
        return is_object($this->file);
    }

    /**
     * Tries to handle the upload request. If the file is not uploaded, returns false. If the file
     * is present in the request, it will create the save object.
     *
     * If the file in the request is chunk, it will create the `ChunkSave` object, otherwise creates the `SingleSave`
     * which doesnt nothing at this moment.
     *
     * @return bool|AbstractSave
     */
    public function receive()
    {
        if (!$this->isUploaded()) {
            return false;
        }

        if ($this->handler->isChunkedUpload()) {
            return new ChunkSave($this->file, $this->handler);
        } else {
            return new SingleSave($this->file, $this->handler);
        }
    }
}