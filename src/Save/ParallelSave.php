<?php

namespace Pion\Laravel\ChunkUpload\Save;

use Illuminate\Http\UploadedFile;
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
        AbstractConfig $config,
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
     * @param float $percentage
     *
     * @return float
     */
    private function pollForAllChunks(float $percentage, $file): float
    {
        $startTime = microtime(true);
        $maxWaitTime = 0.5; // 500ms

        while ((microtime(true) - $startTime) < $maxWaitTime) {
            usleep(100000); // Wait 100ms

            // Re-check for chunks
            $this->foundChunks = $this->getSavedChunksFiles()->all();
            $percentage = floor(count($this->foundChunks) / $this->handler()->getTotalChunks() * 100);
            $this->handler()->setPercentageDone($percentage);

            logger()->debug('chunk-upload.parallel.polling', $this->buildChunkDebugContext([
                'stored_chunk_path' => $file,
                'found_chunks' => array_values($this->foundChunks),
                'found_chunks_count' => count($this->foundChunks),
            ]));

            if ($percentage >= 100) {
                return $percentage;
            }
        }

        return $percentage;
    }

    /**
     * Scans the current chunks files and calculates percentage agains total chunks that there should be.
     */
    private function chunkUploadPercentage(): float
    {
        // Found current number of chunks to determine if we have all chunks (we cant use the
        // index because order of chunks is different.
        $this->foundChunks = $this->getSavedChunksFiles()->all();

        $percentage = floor(count($this->foundChunks) / $this->handler()->getTotalChunks() * 100);

        // We need to update the handler with correct percentage
        $this->handler()->setPercentageDone($percentage);

        return $percentage;
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
        $partFileName = $this->getChunkPartFileName();
        $this->file->move($this->getChunkDirectory(true), $partFileName);
        $file = $this->getChunkDirectory().$partFileName;

        $percentage = $this->chunkUploadPercentage();

        // Some frontend requires us to send data about the upload on the final chunk,
        // determine how we should do this. Some frontends do not care (parsing every request).
        if ($this->handler()->requiresFinalChunkOnLastChunk()) {
            if ($this->handler()->isLastChunk()) {
                $this->isLastChunk = true;
                // Poll for 500ms in 100ms intervals to check if all chunks are present
                // If we have parallel requests, we can get the last chunk by a frontend
                // that requires us to send "information" about the
                if ($percentage < 100) {
                    $this->pollForAllChunks($percentage, $file);
                }
            }
        } else {
            $this->isLastChunk = $percentage >= 100;
        }

        logger()->debug('chunk-upload.parallel.chunk-stored', $this->buildChunkDebugContext([
            'stored_chunk_path' => $file,
            'found_chunks' => array_values($this->foundChunks),
            'found_chunks_count' => count($this->foundChunks),
            'is_last_chunk' => $this->isLastChunk,
        ]));

        return $this;
    }

    /**
     * Searches for all chunk files.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getSavedChunksFiles()
    {
        return $this->chunkStorage->filesByDirectory($this->getChunkDirectory());
    }

    /**
     * @throws MissingChunkFilesException
     * @throws ChunkSaveException
     */
    protected function buildFullFileFromChunks()
    {
        $chunkFiles = $this->foundChunks;

        if (0 === count($chunkFiles)) {
            logger()->debug('chunk-upload.parallel.merge-missing-chunks', $this->buildChunkDebugContext([
                'found_chunks' => [],
                'found_chunks_count' => 0,
            ]));
            throw new MissingChunkFilesException();
        }

        // Sort the chunk order
        natcasesort($chunkFiles);

        $finalFilePath = $this->getChunkFullFilePath();

        // Delete the file if exists
        if (file_exists($finalFilePath)) {
            @unlink($finalFilePath);
        }

        logger()->debug('chunk-upload.parallel.merge-starting', $this->buildChunkDebugContext([
            'chunk_files' => array_values($chunkFiles),
            'chunk_files_count' => count($chunkFiles),
            'final_file_path' => $finalFilePath,
        ]));

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
        $this->chunkDisk()->deleteDirectory($this->getChunkDirectory());

        // Build the chunk file instance
        $this->fullChunkFile = $this->createFullChunkFile($finalFilePath);

        logger()->info('chunk-upload.parallel.merge-finished', $this->buildChunkDebugContext([
            'final_file_path' => $finalFilePath,
            'chunk_files_count' => count($chunkFiles),
        ]));
    }

    private function buildChunkDebugContext(array $context = []): array
    {
        $handler = $this->handler();

        return array_merge([
            'handler' => get_class($handler),
            'client_original_name' => $this->file->getClientOriginalName(),
            'percentage_done' => $handler->getPercentageDone(),
            'total_chunks' => $handler->getTotalChunks(),
            'chunk_name' => $this->chunkFileName,
        ], $context);
    }

    /**
     * Returns the part filename relative to the upload-local chunk directory.
     *
     * @return string
     */
    protected function getChunkPartFileName()
    {
        if (preg_match('/\.([\d]+)\.'.ChunkStorage::CHUNK_EXTENSION.'$/', $this->chunkFileName, $matches)) {
            return $matches[1].'.'.ChunkStorage::CHUNK_EXTENSION;
        }

        return $this->chunkFileName;
    }
}
