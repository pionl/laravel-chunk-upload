<?php

namespace Pion\Laravel\ChunkUpload\Save;

use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;
use Pion\Laravel\ChunkUpload\FileMerger;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;

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
    protected $chunkFullFilePath;

    /**
     * The merged chunk file path relative to the chunk disk.
     *
     * @var string
     */
    protected $chunkRelativeFilePath;

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

        $this->chunkRelativeFilePath = $this->getChunkFullFilePathFromStorage();
        $this->chunkFullFilePath = $this->getChunkFullFilePathFromStorage(true);

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
     * Returns the file id used for upload-local storage paths.
     *
     * @return string
     */
    public function getChunkFileId()
    {
        $chunkFileName = $this->chunkFileName;

        if ($this instanceof ParallelSave) {
            $chunkFileName = preg_replace(
                '/\.[\d]+\.'.ChunkStorage::CHUNK_EXTENSION.'$/',
                '',
                $chunkFileName
            );
        }

        return preg_replace('/\.'.ChunkStorage::CHUNK_EXTENSION.'$/', '', $chunkFileName);
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
     * Returns the full file path relative to the chunk disk.
     *
     * @return string
     */
    public function getChunkFullFileRelativePath()
    {
        return $this->chunkRelativeFilePath;
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

        $paths[] = $this->chunkStorage()->directoryForFile($this->getChunkFileId());

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
        if ($this->handler()->isFirstChunk() && $this->chunkDisk()->exists($this->getChunkFullFileRelativePath())) {
            $this->chunkDisk()->delete($this->getChunkFullFileRelativePath());
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
        // We must pass the true as test to force the upload file
        // to use a standard copy method, not move uploaded file
        $test = true;
        $clientOriginalName = $this->file->getClientOriginalName();
        $clientMimeType = $this->file->getClientMimeType();
        $error = $this->file->getError();

        // Passing a size as the 4th (filesize) argument to the constructor is deprecated since Symfony 4.1.
        // Note: Symfony 8.1+ does not contain Symfony Kernel which is installed on L13 with with PHP 8.4+
        if (!class_exists(SymfonyKernel::class, false) || SymfonyKernel::VERSION_ID >= 40100) {
            return new UploadedFile($finalPath, $clientOriginalName, $clientMimeType, $error, $test);
        }

        $fileSize = filesize($finalPath);

        return new UploadedFile($finalPath, $clientOriginalName, $clientMimeType, $fileSize, $error, $test);
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
     * @return \Illuminate\Filesystem\FilesystemAdapter
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

    /**
     * Returns the path for the merged chunk file.
     *
     * @param bool $absolutePath
     *
     * @return string
     */
    protected function getChunkFullFilePathFromStorage($absolutePath = false)
    {
        $path = $this->chunkStorage()->mergedFilePathForFile($this->getChunkFileId());

        if (!$absolutePath) {
            return $path;
        }

        return $this->chunkStorage()->getDiskPathPrefix().$path;
    }
}
