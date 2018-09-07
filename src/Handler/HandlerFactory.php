<?php
/**
 *
 */

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class HandlerFactory
{
    /**
     * List of current handlers
     * @var array
     */
    static protected $handlers = [];

    /**
     * The fallback handler to use
     * @var string
     */
    static protected $fallbackHandler = null;

    /**
     * Returns handler class based on the request or the fallback handler
     *
     * @param Request     $request
     * @param string|null $fallbackClass
     *
     * @return string
     */
    public static function classFromRequest(Request $request, $fallbackClass = null)
    {
        /** @var AbstractUploadHandler $handlerClass */
        foreach (static::$handlers as $handlerClass) {
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
     * Adds a custom handler class
     *
     * @param string $handlerClass
     */
    public static function register($handlerClass)
    {
        static::$handlers[] = $handlerClass;
    }

    /**
     * Sets the default fallback handler when the detection fails
     *
     * @param string $fallbackHandler
     */
    public function setFallbackHandler($fallbackHandler)
    {
        static::$fallbackHandler = $fallbackHandler;
    }
}