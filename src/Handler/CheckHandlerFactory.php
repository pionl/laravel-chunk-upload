<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CheckHandlerFactory
{
    /**
     * List of current handlers
     * @var array
     */
    static protected $handlers = array(
        ResumableJSCheckHandler::class,
        FileCheckHandler::class,
    );

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
            throw new BadRequestHttpException();
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
}
