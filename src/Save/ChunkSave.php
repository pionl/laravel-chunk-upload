<?php
namespace Pion\Laravel\ChunkUpload\Save;

use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ChunkSave extends AbstractSave
{
    /**
     * Is this the final chunk?
     * @var bool
     */
    protected $isLastChunk;

    /**
     * What is the chunk file name
     * @var string
     */
    protected $chunkFileName;

    /**
     * The chunk file path
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
    private $chunkStorage;

    /**
     * AbstractUpload constructor.
     *
     * @param UploadedFile    $file         the uploaded file (chunk file)
     * @param AbstractHandler $handler      the handler that detected the correct save method
     * @param ChunkStorage    $chunkStorage the chunk storage
     * @param AbstractConfig  $config       the config manager
     */
    public function __construct(UploadedFile $file, AbstractHandler $handler, $chunkStorage, $config)
    {
        parent::__construct($file, $handler, $config);
        $this->chunkStorage = $chunkStorage;

        $this->isLastChunk = $handler->isLastChunk();
        $this->chunkFileName = $handler->getChunkFileName();

        // buid the full disk path
        $this->chunkFullFilePath = $this->chunkStorage()->getDiskPathPrefix().$this->getChunkFilePath();

        $this->handleChunkMerge();
    }


    /**
     * Checks if the file upload is finished (last chunk)
     * 
     * @return bool
     */
    public function isFinished()
    {
        return parent::isFinished() && $this->isLastChunk;
    }

    /**
     * Returns the chunk file path in the current disk instance
     *
     * @return string
     */
    public function getChunkFilePath()
    {
        return $this->getChunkDirectory().$this->chunkFileName;
    }

    /**
     * Returns the full file path
     * @return string
     */
    public function getChunkFullFilePath()
    {
        return $this->chunkFullFilePath;
    }

    /**
     * Returns the folder for the cunks in the storage path on current disk instance
     *
     * @return string
     */
    public function getChunkDirectory()
    {
        return $this->chunkStorage()->directory();
    }

    /**
     * Returns the uploaded file if the chunk if is not completed, otherwise passes the
     * final chunk file
     *
     * @return null|UploadedFile
     */
    public function getFile()
    {
        if ($this->isLastChunk) {
            return $this->fullChunkFile;
        }

        return parent::getFile();
    }

    /**
     * Appends the new uploaded data to the final file
     *
     * @throws ChunkSaveException
     */
    protected function handleChunkMerge()
    {
        // prepare the folder and file path
        $this->createChunksFolderIfNeeded();
        $file = $this->getChunkFilePath();

        // delete the old chunk
        if ($this->handler()->isFirstChunk() && $this->chunkDisk()->exists($file)) {
            $this->chunkDisk()->delete($file);
        }

        // passes the uploaded data
        $this->appendDataToChunkFile();

        // build the last file becouse of the last chunk
        if ($this->isLastChunk) {
            $this->buildFullFileFromChunks();
        }
    }


    /**
     * Builds the final file
     */
    protected function buildFullFileFromChunks()
    {
        // try to get local path
        $finalPath = $this->getChunkFullFilePath();

        // build the new UploadedFile
        $this->fullChunkFile = new UploadedFile(
            $finalPath,
            $this->file->getClientOriginalName(),
            $this->file->getClientMimeType(),
            filesize($finalPath), $this->file->getError(),
            true // we must pass the true as test to force the upload file
                // to use a standart copy method, not move uploaded file
        );
    }

    /**
     * Appends the current uploaded file data to a chunk file
     *
     * @throws ChunkSaveException
     */
    protected function appendDataToChunkFile()
    {
        // @todo: rebuild to use updateStream and etc to try to enable cloud
        // $driver = $this->chunkStorage()->driver();

        // open the target file
        if (!$out = @fopen($this->getChunkFullFilePath(), 'ab')) {
            throw new ChunkSaveException('Failed to open output stream.', 102);
        }

        // open the new uploaded chunk
        if (!$in = @fopen($this->file->getPathname(), 'rb')) {
            @fclose($out);
            throw new ChunkSaveException('Failed to open input stream', 101);
        }

        // read and write in buffs
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        // close the readers
        @fclose($out);
        @fclose($in);
    }

    /**
     * Returns the current chunk storage
     *
     * @return ChunkStorage
     */
    public function chunkStorage()
    {
        return $this->chunkStorage;
    }

    /**
     * Returns the disk adapter for the chunk
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    public function chunkDisk()
    {
        return $this->chunkStorage()->disk();
    }

    /**
     * Crates the chunks folder if doesnt exists. Uses recursive create
     */
    protected function createChunksFolderIfNeeded()
    {
        $path = $this->getChunksPath();

        // creates the chunks dir
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }
}