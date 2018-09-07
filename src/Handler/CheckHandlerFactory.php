<?php
namespace Pion\Laravel\ChunkUpload\Handler;

class CheckHandlerFactory extends HandlerFactory
{
    /**
     * List of current handlers
     * @var array
     */
    static protected $handlers = [
        ResumableJSCheckHandler::class,
        FileCheckHandler::class,
    ];
}
