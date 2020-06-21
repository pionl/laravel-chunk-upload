<?php

namespace Pion\Laravel\ChunkUpload\Save;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Pion\Laravel\ChunkUpload\ChunkFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;
use Pion\Laravel\ChunkUpload\Exceptions\MissingChunkFilesException;
use Pion\Laravel\ChunkUpload\FileMerger;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\Traits\HandleParallelUploadTrait;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

/**
 * Class ParallelSave.
 *
 * @method HandleParallelUploadTrait|AbstractHandler handler()
 */
class ParallelSave extends ChunkSave
{
    /**
     * Stored on construct - the file is moved and isValid will return false.
     *
     * @var bool
     */
    protected $isFileValid;

    /**
     * @var array
     */
    protected $foundChunks = [];

    /**
     * ParallelSave constructor.
     *
     * @param UploadedFile                              $file         the uploaded file (chunk file)
     * @param AbstractHandler|HandleParallelUploadTrait $handler      the handler that detected the correct save method
     * @param ChunkStorage                              $chunkStorage the chunk storage
     * @param AbstractConfig                            $config       the config manager
     *
     * @throws ChunkSaveException
     */
    public function __construct(
        UploadedFile $file,
        AbstractHandler $handler,
        ChunkStorage $chunkStorage,
        AbstractConfig $config
    ) {
        // Get current file validation - the file instance is changed
        $this->isFileValid = $file->isValid();

        // Handle the file upload
        parent::__construct($file, $handler, $chunkStorage, $config);
    }

    public function isValid()
    {
        return $this->isFileValid;
    }

    /**
     * Moves the uploaded chunk file to separate chunk file for merging.
     *
     * @param string $file Relative path to chunk
     *
     * @return $this
     */
    protected function handleChunkFile($file)
    {
        // Move the uploaded file to chunk folder
        $this->file->move($this->getChunkDirectory(true), $this->chunkFileName);

        // Found current number of chunks to determine if we have all chunks (we cant use the
        // index because order of chunks are different.
        $this->foundChunks = $this->getSavedChunksFiles()->all();

        $percentage = floor((count($this->foundChunks)) / $this->handler()->getTotalChunks() * 100);
        // We need to update the handler with correct percentage
        $this->handler()->setPercentageDone($percentage);
        $this->isLastChunk = $percentage >= 100;

        return $this;
    }

    /**
     * Searches for all chunk files.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getSavedChunksFiles()
    {
        $chunkFileName = preg_replace(
            "/\.[\d]+\.".ChunkStorage::CHUNK_EXTENSION.'$/', '', $this->handler()->getChunkFileName()
        );

        return $this->chunkStorage->files(function ($file) use ($chunkFileName) {
            return false === Str::contains($file, $chunkFileName);
        });
    }

    /**
     * @throws MissingChunkFilesException
     * @throws ChunkSaveException
     */
    protected function buildFullFileFromChunks()
    {
        $chunkFiles = $this->foundChunks;

        if (0 === count($chunkFiles)) {
            throw new MissingChunkFilesException();
        }

        // Sort the chunk order
        natcasesort($chunkFiles);

        // Get chunk files that matches the current chunk file name, also sort the chunk
        // files.
        $rootDirectory = $this->getChunkDirectory(true);
        $finalFilePath = $rootDirectory.'./'.$this->handler()->createChunkFileName();

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
