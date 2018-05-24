<?php

namespace Pion\Laravel\ChunkUpload\Save;

use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;
use Pion\Laravel\ChunkUpload\Exceptions\MissingChunkFilesException;
use Pion\Laravel\ChunkUpload\FileMerger;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\ChunkFile;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ParallelSave extends ChunkSave
{
    protected $totalChunks;
    /**
     * Stored on construct - the file is moved and isValid will return false
     * @var bool
     */
    protected $isFileValid;

    /**
     * ParallelSave constructor.
     *
     * @param int|string      $totalChunks
     * @param UploadedFile    $file         the uploaded file (chunk file)
     * @param AbstractHandler $handler      the handler that detected the correct save method
     * @param ChunkStorage    $chunkStorage the chunk storage
     * @param AbstractConfig  $config       the config manager
     *
     * @throws ChunkSaveException
     */
    public function __construct(
        $totalChunks,
        UploadedFile $file,
        AbstractHandler $handler,
        ChunkStorage $chunkStorage,
        AbstractConfig $config
    ) {
        $this->totalChunks = intval($totalChunks);
        $this->isFileValid = $file->isValid();
        parent::__construct($file, $handler, $chunkStorage, $config);
    }

    public function isValid()
    {
        return $this->isFileValid;
    }


    /**
     * Searches for all chunk files
     * @return \Illuminate\Support\Collection
     */
    protected function savedChunksFiles()
    {
        return $this->chunkStorage()->files(function ($file) {
            return !preg_match("/\.[\d]+\.".ChunkStorage::CHUNK_EXTENSION."$/", $file);
        });
    }

    /**
     * Handles the chunk merging
     * @throws ChunkSaveException
     */
    protected function handleChunk()
    {
        $files = $this->savedChunksFiles();
        // We need to detect if this is last chunk - get all uploaded chunks (and increase the count by 1 for this
        // un-moved chunk) - it's safer due possibility of un-ordered chunks
        $this->isLastChunk = ($files->count() + 1) === $this->totalChunks;

        parent::handleChunk();
    }

    /**
     * Moves the uploaded chunk file to separate chunk file for merging
     *
     * @param string $file Relative path to chunk
     *
     * @return $this
     */
    protected function handleChunkFile($file)
    {
        // Move the uploaded file to chunk folder
        $this->file->move($this->getChunkDirectory(true), $this->chunkFileName);
        return $this;
    }

    /**
     * @throws MissingChunkFilesException
     * @throws ChunkSaveException
     */
    protected function buildFullFileFromChunks()
    {
        $chunkFiles = $this->savedChunksFiles()->all();

        if (count($chunkFiles) === 0) {
            throw new MissingChunkFilesException();
        }

        // Sort the chunk order
        natcasesort($chunkFiles);

        // Get chunk files that matches the current chunk file name, also sort the chunk
        // files.
        $finalFilePath = $this->getChunkDirectory(true).'./'.$this->handler()->createChunkFileName();
        // Delete the file if exists
        if (file_exists($finalFilePath)) {
            @unlink($finalFilePath);
        }

        $fileMerger = new FileMerger($finalFilePath);

        // Append each chunk file
        foreach ($chunkFiles as $filePath) {
            // Build the chunk file
            $chunkFile = new ChunkFile($filePath, null, $this->chunkStorage());

            // Append the data
            $fileMerger->appendFile($chunkFile->getAbsolutePath());

            // Delete the chunk file
            $chunkFile->delete();
        }

        $fileMerger->close();

        // Build the chunk file instance
        $this->fullChunkFile = $this->createFullChunkFile($finalFilePath);
    }
}
