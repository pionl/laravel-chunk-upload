<?php

namespace Pion\Laravel\ChunkUpload\Save;

use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class AbstractSave.
 *
 * Handles the save handling of the file.
 * You can call all function you know from the UploadedFile
 *
 * @method string|null getClientOriginalName()
 * @method string      getClientOriginalExtension()
 * @method string|null getClientMimeType()
 * @method string|null guessClientExtension()
 * @method int|null    getClientSize()
 * @method int         getError()
 * @method File        move($directory, $name = null)
 */
abstract class AbstractSave
{
    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * @var AbstractHandler
     */
    private $handler;

    /**
     * Returns the config for the upload.
     *
     * @var AbstractConfig
     */
    private $config;

    /**
     * AbstractUpload constructor.
     *
     * @param UploadedFile    $file    the uploaded file (chunk file)
     * @param AbstractHandler $handler the handler that detected the correct save method
     * @param AbstractConfig  $config  the config manager
     */
    public function __construct(UploadedFile $file, AbstractHandler $handler, $config)
    {
        $this->file = $file;
        $this->handler = $handler;
        $this->config = $config;
    }

    /**
     * Checks if the file upload is finished.
     *
     * @return bool
     */
    public function isFinished()
    {
        return $this->isValid();
    }

    /**
     * Checks if the upload is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->file->isValid();
    }

    /**
     * Returns the error message.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->file->getErrorMessage();
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Returns always the uploaded chunk file.
     *
     * @return UploadedFile|null
     */
    public function getUploadedFile()
    {
        return $this->file;
    }

    /**
     * Passes all the function into the file.
     *
     * @param $name      string
     * @param $arguments array
     *
     * @return mixed
     *
     * @see http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getFile(), $name], $arguments);
    }

    /**
     * @return AbstractHandler
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * Returns the current config.
     *
     * @return AbstractConfig
     */
    public function config()
    {
        return $this->config;
    }
}
