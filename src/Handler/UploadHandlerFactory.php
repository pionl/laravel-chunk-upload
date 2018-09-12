<?php
namespace Pion\Laravel\ChunkUpload\Handler;

class UploadHandlerFactory extends HandlerFactory
{
    /**
     * The fallback handler to use
     * @var string
     */
    static protected $fallbackHandler = SingleUploadHandler::class;

    /**
     * Decides if the implementation of the factory supports a specific handler or not.
     *
     * @param $handlerClass
     *
     * @return bool
     */
    protected static function filterHandler($handlerClass)
    {
        return is_subclass_of($handlerClass, AbstractUploadHandler::class);
    }
}
