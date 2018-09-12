<?php
namespace Pion\Laravel\ChunkUpload\Handler;

class CheckHandlerFactory extends HandlerFactory
{
    /**
     * Decides if the implementation of the factory supports a specific handler or not.
     *
     * @param $handlerClass
     *
     * @return bool
     */
    protected static function filterHandler($handlerClass)
    {
        return is_subclass_of($handlerClass, AbstractCheckHandler::class);
    }
}
