<?php

namespace Pion\Laravel\ChunkUpload\Exceptions;

class UploadFailedException extends \Exception
{
    public function __construct($message, $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
