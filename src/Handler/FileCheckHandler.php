<?php
/**
 *
 */

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class FileCheckHandler extends AbstractCheckHandler
{
    const FILENAME_INDEX = 'name';

    const FILE_SIZE_INDEX = 'size';

    private $bytesTotal;

    public function __construct(Request $request, $config)
    {
        parent::__construct($request, $config);

        $this->bytesTotal = $request->get(self::FILE_SIZE_INDEX);
    }

    /**
     * Returns the filename from the request
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getFilenameFromRequest(Request $request)
    {
        return $request->get(self::FILENAME_INDEX);
    }

    /**
     * Returns the chunk file name for a storing the tmp file
     *
     * @return string
     */
    public function getChunkFileName()
    {
        return $this->createChunkFileName($this->bytesTotal);
    }

    /**
     * Checks if the current abstract handler can be used via HandlerFactory
     *
     * @param Request $request
     *
     * @return bool
     */
    public static function canBeUsedForRequest(Request $request)
    {
        return $request->has(self::FILENAME_INDEX, self::FILE_SIZE_INDEX);
    }

    /**
     * Checks if the target file or chunk is already uploaded
     *
     * @param ChunkStorage $chunkStorage
     * @return false|array
     */
    public function check($chunkStorage)
    {
        $path[] = $chunkStorage->getDiskPathPrefix();
        $path[] = $chunkStorage->directory();
        $path[] = $this->getChunkFileName();

        $chunkFullFilePath = implode('', $path);

        if(!\File::exists($chunkFullFilePath)) {
            return [
                'name' => $this->filename,
                'size' => 0,
            ];
        }

        return [
            'name' => $this->filename,
            'size' => \File::size($chunkFullFilePath),
        ];
    }
}