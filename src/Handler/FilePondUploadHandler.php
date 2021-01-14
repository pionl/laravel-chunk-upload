<?php

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Save\AbstractSave;
use Pion\Laravel\ChunkUpload\Save\ChunkSave;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class FilePondUploadHandler extends AbstractHandler
{
    const CHUNK_UUID_INDEX = 'patch';

    const HEADER_UPLOAD_LENGTH = 'Upload-Length';
    const HEADER_UPLOAD_OFFSET = 'Upload-Offset';
    const HEADER_UPLOAD_NAME = 'Upload-Name';

    /**
     * Checks if the current abstract handler can be used via HandlerFactory.
     *
     * @param Request $request
     *
     * @return bool
     */
    public static function canBeUsedForRequest(Request $request)
    {
        return $request->hasHeader(self::HEADER_UPLOAD_OFFSET);
    }

    /**
     * FilePond uploads chunks by just putting the data right in the PATCH body.
     * There is no UploadedFile created on the request. We have to save the payload
     * to a temporary file and create it ourself.
     *
     * @param $fileIndex
     * @param Request $request
     *
     * @return UploadedFile|null
     */
    public static function getUploadedFile($fileIndex, Request $request)
    {
        $path = tempnam(sys_get_temp_dir(), $fileIndex . "_");
        file_put_contents($path, $request->getContent());

        // We need to pretend like we have an UploadedFile, while skipping the normal
        // validation that verifies a file was uploaded properly.
        return new class($path, $request->header(self::HEADER_UPLOAD_NAME)) extends UploadedFile {
            public function isValid()
            {
                return true;
            }
        };
    }

    /**
     * @return int
     */
    public function getTotalSize()
    {
        return $this->request->header(self::HEADER_UPLOAD_LENGTH);
    }

    /**
     * @return int
     */
    public function getChunkOffset()
    {
        return $this->request->header(self::HEADER_UPLOAD_OFFSET);
    }

    /**
     * @return int
     */
    public function getChunkSize()
    {
        return $this->file->getSize();
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->request->header(self::HEADER_UPLOAD_NAME);
    }

    /**
     * @return string
     */
    public function getFileUUID()
    {
        return $this->request->get(self::CHUNK_UUID_INDEX);
    }

    /**
     * Creates save instance and starts saving the uploaded file.
     *
     * @param ChunkStorage $chunkStorage the chunk storage
     *
     * @return AbstractSave
     */
    public function startSaving($chunkStorage)
    {
        return new ChunkSave($this->file, $this, $chunkStorage, $this->config);
    }

    /**
     * Returns the chunk file name for a storing the tmp file.
     *
     * @return string
     */
    public function getChunkFileName()
    {
        return $this->createChunkFileName($this->getFileUUID(), $this->getChunkOffset());
    }

    /**
     * Checks if the request has first chunk.
     *
     * @return bool
     */
    public function isFirstChunk()
    {
        return $this->getChunkOffset() == 0;
    }

    /**
     * Checks if the current request has the last chunk.
     *
     * @return bool
     */
    public function isLastChunk()
    {
        return $this->getChunkOffset() + $this->getChunkSize() == $this->getTotalSize();
    }

    /**
     * Checks if the current request is chunked upload.
     *
     * @return bool
     */
    public function isChunkedUpload()
    {
        return true;
    }

    /**
     * Returns the percentage of the upload file.
     *
     * @return int
     */
    public function getPercentageDone()
    {
        return ($this->getChunkOffset() + $this->getChunkSize()) / $this->getTotalSize() * 100;
    }
}