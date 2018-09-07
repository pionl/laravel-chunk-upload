<?php
namespace Pion\Laravel\ChunkUpload\Handler;

class UploadHandlerFactory extends HandlerFactory
{
    /**
     * List of current handlers
     * @var array
     */
    static protected $handlers = [
        ContentRangeUploadHandler::class,
        ChunksInRequestUploadHandler::class,
        ResumableJSUploadHandler::class,
        DropZoneUploadHandler::class,
        ChunksInRequestSimpleUploadHandler::class,
        NgFileUploadHandler::class,
    ];

    /**
     * The fallback handler to use
     * @var string
     */
    static protected $fallbackHandler = SingleUploadHandler::class;
}
