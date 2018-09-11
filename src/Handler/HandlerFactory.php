<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;

class HandlerFactory
{
    /**
     * List of current handlers.
     *
     * @var array
     */
    protected static $handlers = [
        ContentRangeUploadHandler::class,
        ChunksInRequestUploadHandler::class,
        ResumableJSUploadHandler::class,
        DropZoneUploadHandler::class,
        ChunksInRequestSimpleUploadHandler::class,
        NgFileUploadHandler::class,
    ];

    /**
     * The fallback handler to use.
     *
     * @var string
     */
    protected static $fallbackHandler = SingleUploadHandler::class;

    /**
     * Returns handler class based on the request or the fallback handler.
     *
     * @param Request     $request
     * @param string|null $fallbackClass
     *
     * @return string
     */
    public static function classFromRequest(Request $request, $fallbackClass = null)
    {
        /** @var AbstractHandler $handlerClass */
        foreach (static::$handlers as $handlerClass) {
            if ($handlerClass::canBeUsedForRequest($request)) {
                return $handlerClass;
                break;
            }
        }

        if (is_null($fallbackClass)) {
            // the default handler
            return static::$fallbackHandler;
        }

        return $fallbackClass;
    }

    /**
     * Adds a custom handler class.
     *
     * @param string $handlerClass
     */
    public static function register($handlerClass)
    {
        static::$handlers[] = $handlerClass;
    }

    /**
     * Overrides the handler list.
     *
     * @param array $handlers
     */
    public static function setHandlers($handlers)
    {
        static::$handlers = $handlers;
    }

    /**
     * Returns the handler list.
     *
     * @return array
     */
    public static function getHandlers()
    {
        return static::$handlers;
    }

    /**
     * Sets the default fallback handler when the detection fails.
     *
     * @param string $fallbackHandler
     */
    public function setFallbackHandler($fallbackHandler)
    {
        static::$fallbackHandler = $fallbackHandler;
    }
}
