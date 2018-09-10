<?php

namespace Pion\Laravel\ChunkUpload\Exceptions;

use Exception;

class UploadMissingFileException extends \Exception
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     *
     * @see  http://php.net/manual/en/exception.construct.php
     *
     * @param string    $message  [optional] The Exception message to throw
     * @param int       $code     [optional] The Exception code
     * @param Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     *
     * @since 5.1.0
     */
    public function __construct($message = 'The request is missing a file', $code = 400, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
