<?php
/**
 *
 */

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

abstract class AbstractCheckHandler extends AbstractHandler
{
    /**
     * AbstractReceiver constructor.
     *
     * @param Request        $request
     * @param AbstractConfig $config
     */
    public function __construct(Request $request, $config)
    {
        parent::__construct($request, $this->getFilenameFromRequest($request), $config);
    }

    /**
     * Returns the filename from the request
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    abstract public function getFilenameFromRequest(Request $request);

    /**
     * Checks if the target file or chunk is already uploaded
     *
     * @param ChunkStorage $chunkStorage
     * @return false|array
     */
    abstract public function check($chunkStorage);
}