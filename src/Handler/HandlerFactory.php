<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class HandlerFactory
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

        ResumableJSCheckHandler::class,
        FileCheckHandler::class,
    ];

    /**
     * The fallback handler to use.
     *
     * @var string
     */
    protected static $fallbackHandler = null;

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
        /** @var AbstractUploadHandler $handlerClass */
        foreach (static::getHandlers() as $handlerClass) {
            if ($handlerClass::canBeUsedForRequest($request)) {
                return $handlerClass;
                break;
            }
        }

        if (is_null($fallbackClass)) {
            if (static::$fallbackHandler === null) {
                throw new BadRequestHttpException();
            }
            // the default handler
            return static::$fallbackHandler;
        }

        return $fallbackClass;
    }

    /**
     * Decides if the implementation of the factory supports a specific handler or not.
     *
     * @param $handlerClass
     *
     * @return bool
     */
    protected static function filterHandler($handlerClass)
    {
        return true;
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
        $handlers = static::$handlers;

        foreach ($handlers as $key => $handlerClass) {
            if (! static::filterHandler($handlerClass)) {
                unset($handlers[$key]);
            }
        }

        return array_values($handlers);
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
