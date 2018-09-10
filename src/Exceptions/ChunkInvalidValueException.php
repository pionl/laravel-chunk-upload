<?php

namespace Pion\Laravel\ChunkUpload\Exceptions;

use Exception;

/**
 * Class ChunkInvalidValueException.
 */
class ChunkInvalidValueException extends \Exception
{
    /**
     * ChunkInvalidValueException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct(
        $message = 'The chunk parameters are invalid',
        $code = 500,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
