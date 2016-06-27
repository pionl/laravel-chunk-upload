<?php
namespace Pion\Laravel\ChunkUpload\Save;

use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
     * @var UploadedFile|null
     */
    protected $fullChunkFile;

    /**
     * AbstractUpload constructor.
     *
     * @param UploadedFile    $file    the uploaded file (chunk file)
     * @param AbstractHandler $handler the handler that detected the correct save method
     */
    public function __construct(UploadedFile $file, AbstractHandler $handler)
    {
        parent::__construct($file, $handler);

        $this->isLastChunk = $handler->isLastChunk();
        $this->chunkFileName = $handler->getChunkFileName();

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
     * Returns the chunk file path
     * @return string
     */
    public function getChunkFilePath()
    {
        return $this->getChunksPath().$this->chunkFileName;
    }

    /**
     * Returns the folder for the cunks in the storage path
     *
     * @return string
     */
    public function getChunksPath()
    {
        return storage_path("chunks/");
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
        if ($this->handler()->isFirstChunk() && file_exists($file)) {
            @unlink($file);
        }

        // passes the uploaded data
        $this->appendData($file);

        // build the last file becouse of the last chunk
        if ($this->isLastChunk) {
            $this->buildFullFileFromChunks($file);
        }
    }

    /**
     * Builds the final file
     *
     * @param string $file
     */
    protected function buildFullFileFromChunks($file)
    {
        $this->fullChunkFile = new UploadedFile(
            $file, $this->file->getClientOriginalName(), $this->file->getClientMimeType(),
            filesize($file), $this->file->getError()
        );
    }

    /**
     * Appends the current uploaded file data
     *
     * @param string $filePathPartial
     *
     * @throws ChunkSaveException
     */
    protected function appendData($filePathPartial)
    {
        // open the target file
        if (!$out = @fopen($filePathPartial, 'ab')) {
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