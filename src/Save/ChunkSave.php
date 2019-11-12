<?php

namespace Pion\Laravel\ChunkUpload\Save;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;
use Pion\Laravel\ChunkUpload\FileMerger;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ChunkSave extends AbstractSave
{
    /**
     * Is this the final chunk?
     *
     * @var bool
     */
    protected $isLastChunk;

    /**
     * What is the chunk file name.
     *
     * @var string
     */
    protected $chunkFileName;

    /**
     * The chunk file path.
     *
     * @var string
     */
    protected $chunkFullFilePath = null;

    /**
     * @var UploadedFile|null
     */
    protected $fullChunkFile;

    /**
     * @var ChunkStorage
     */
    protected $chunkStorage;

    /**
     * AbstractUpload constructor.
     *
     * @param UploadedFile    $file         the uploaded file (chunk file)
     * @param AbstractHandler $handler      the handler that detected the correct save method
     * @param ChunkStorage    $chunkStorage the chunk storage
     * @param AbstractConfig  $config       the config manager
     *
     * @throws ChunkSaveException
     */
    public function __construct(UploadedFile $file, AbstractHandler $handler, $chunkStorage, $config)
    {
        parent::__construct($file, $handler, $config);
        $this->chunkStorage = $chunkStorage;

        $this->isLastChunk = $handler->isLastChunk();
        $this->chunkFileName = $handler->getChunkFileName();

        // build the full disk path
        $this->chunkFullFilePath = $this->getChunkFilePath(true);

        $this->handleChunk();
    }

    /**
     * Checks if the file upload is finished (last chunk).
     *
     * @return bool
     */
    public function isFinished()
    {
        return parent::isFinished() && $this->isLastChunk;
    }

    /**
     * Returns the chunk file path in the current disk instance.
     *
     * @param bool $absolutePath
     *
     * @return string
     */
    public function getChunkFilePath($absolutePath = false)
    {
        return $this->getChunkDirectory($absolutePath).$this->chunkFileName;
    }

    /**
     * Returns the full file path.
     *
     * @return string
     */
    public function getChunkFullFilePath()
    {
        return $this->chunkFullFilePath;
    }

    /**
     * Returns the folder for the cunks in the storage path on current disk instance.
     *
     * @param bool $absolutePath
     *
     * @return string
     */
    public function getChunkDirectory($absolutePath = false)
    {
        $paths = [];

        if ($absolutePath) {
            $paths[] = $this->chunkStorage()->getDiskPathPrefix();
        }

        $paths[] = $this->chunkStorage()->directory();

        return implode('', $paths);
    }

    /**
     * Returns the uploaded file if the chunk if is not completed, otherwise passes the
     * final chunk file.
     *
     * @return UploadedFile|null
     */
    public function getFile()
    {
        if ($this->isLastChunk) {
            return $this->fullChunkFile;
        }

        return parent::getFile();
    }

    /**
     * @deprecated
     * @since v1.1.8
     */
    protected function handleChunkMerge()
    {
        $this->handleChunk();
    }

    /**
     * Appends the new uploaded data to the final file.
     *
     * @throws ChunkSaveException
     */
    protected function handleChunk()
    {
        // prepare the folder and file path
        $this->createChunksFolderIfNeeded();
        $file = $this->getChunkFilePath();

        $this->handleChunkFile($file)
             ->tryToBuildFullFileFromChunks();
    }

    /**
     * Checks if the current chunk is last.
     *
     * @return $this
     */
    protected function tryToBuildFullFileFromChunks()
    {
        // build the last file because of the last chunk
        if ($this->isLastChunk) {
            $this->buildFullFileFromChunks();
        }

        return $this;
    }

    /**
     * Appends the current uploaded file to chunk file.
     *
     * @param string $file Relative path to chunk
     *
     * @return $this
     *
     * @throws ChunkSaveException
     */
    protected function handleChunkFile($file)
    {
        // delete the old chunk
        if ($this->handler()->isFirstChunk() && $this->chunkDisk()->exists($file)) {
            $this->chunkDisk()->delete($file);
        }

        // Append the data to the file
        (new FileMerger($this->getChunkFullFilePath()))
            ->appendFile($this->file->getPathname())
            ->close();

        return $this;
    }

    /**
     * Builds the final file.
     */
    protected function buildFullFileFromChunks()
    {
        // try to get local path
        $finalPath = $this->getChunkFullFilePath();

        // build the new UploadedFile
        $this->fullChunkFile = $this->createFullChunkFile($finalPath);
    }

    /**
     * Creates the UploadedFile object for given chunk file.
     *
     * @param string $finalPath
     *
     * @return UploadedFile
     */
    protected function createFullChunkFile($finalPath)
    {
        return new UploadedFile(
            $finalPath,
            $this->file->getClientOriginalName(),
            $this->file->getClientMimeType(),
            filesize($finalPath),
            $this->file->getError(),
            // we must pass the true as test to force the upload file
            // to use a standard copy method, not move uploaded file
            true
        );
    }

    /**
     * Returns the current chunk storage.
     *
     * @return ChunkStorage
     */
    public function chunkStorage()
    {
        return $this->chunkStorage;
    }

    /**
     * Returns the disk adapter for the chunk.
     *
     * @return FilesystemContract
     */
    public function chunkDisk()
    {
        return $this->chunkStorage()->disk();
    }

    /**
     * Crates the chunks folder if doesn't exists. Uses recursive create.
     */
    protected function createChunksFolderIfNeeded()
    {
        $path = $this->getChunkDirectory(true);

        // creates the chunks dir
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }
}
