<?php

namespace Pion\Laravel\ChunkUpload\Exceptions;

use Throwable;

class MissingChunkFilesException extends \Exception
{
    public function __construct(
        $message = 'Logic did not find any chunk file - check the folder configuration',
        $code = 500,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
