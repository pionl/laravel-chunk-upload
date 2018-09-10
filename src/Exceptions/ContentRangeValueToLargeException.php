<?php

namespace Pion\Laravel\ChunkUpload\Exceptions;

use Exception;

class ContentRangeValueToLargeException extends \Exception
{
    public function __construct(
        $message = 'The content range value is to large',
        $code = 500,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
