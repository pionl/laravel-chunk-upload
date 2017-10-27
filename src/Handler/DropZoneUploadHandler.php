<?php
namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;

class DropZoneUploadHandler extends ChunksInRequestUploadHandler
{
    const CHUNK_UUID_INDEX = 'dzuuid';
    const CHUNK_INDEX = 'dzchunkindex';
    const CHUNK_FILE_SIZE_INDEX = 'dztotalfilesize';
    const CHUNK_SIZE_INDEX = 'dzchunksize';
    const CHUNK_COUNT_INDEX = 'dztotalchunkcount';
    const CHUNK_OFFSET_INDEX = 'dzchunkbyteoffset';

    /**
     * The DropZone file uuid
     * @var string|null
     */
    protected $fileUuid = null;

    /**
     * AbstractReceiver constructor.
     *
     * @param Request        $request
     * @param UploadedFile   $file
     * @param AbstractConfig $config
     */
    public function __construct(Request $request, $file, $config)
    {
        parent::__construct($request, $file, $config);

        $this->fileUuid = $request->get(self::CHUNK_UUID_INDEX);
    }

    public function getChunkFileName()
    {
        return $this->createChunkFileName($this->fileUuid);
    }
    /**
     * Returns current chunk from the request
     *
     * @param Request $request
     *
     * @return int
     */
    protected function getCurrentChunkFromRequest(Request $request)
    {
        return intval($request->get(self::CHUNK_INDEX)) + 1;
    }

    /**
     * Returns current chunk from the request
     *
     * @param Request $request
     *
     * @return int
     */
    protected function getTotalChunksFromRequest(Request $request)
    {
        return intval($request->get(self::CHUNK_COUNT_INDEX));
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
        return $request->has(self::CHUNK_UUID_INDEX) && $request->has(self::CHUNK_COUNT_INDEX) &&
            $request->has(self::CHUNK_INDEX);
    }
}