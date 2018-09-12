<?php

namespace Pion\Laravel\ChunkUpload;

use Pion\Laravel\ChunkUpload\Exceptions\ChunkSaveException;

class FileMerger
{
    /**
     * @var bool|resource
     */
    protected $destinationFile;

    /**
     * FileMerger constructor.
     *
     * @param string $targetFile
     *
     * @throws ChunkSaveException
     */
    public function __construct($targetFile)
    {
        // open the target file
        if (!$this->destinationFile = @fopen($targetFile, 'ab')) {
            throw new ChunkSaveException('Failed to open output stream.', 102);
        }
    }

    /**
     * Appends given file.
     *
     * @param string $sourceFilePath
     *
     * @return $this
     *
     * @throws ChunkSaveException
     */
    public function appendFile($sourceFilePath)
    {
        // open the new uploaded chunk
        if (!$in = @fopen($sourceFilePath, 'rb')) {
            @fclose($this->destinationFile);
            throw new ChunkSaveException('Failed to open input stream', 101);
        }

        // read and write in buffs
        while ($buff = fread($in, 4096)) {
            fwrite($this->destinationFile, $buff);
        }

        @fclose($in);

        return $this;
    }

    /**
     * Closes the connection to the file.
     */
    public function close()
    {
        @fclose($this->destinationFile);
    }
}
