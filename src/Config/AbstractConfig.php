<?php
namespace Pion\Laravel\ChunkUpload\Config;

abstract class AbstractConfig
{
    /**
     * Returns the config from the aplication container
     *
     * @return AbstractConfig
     *
     * @see app()
     */
    public static function config()
    {
        return app(AbstractConfig::class);
    }

    /**
     * Returns the disk name to use for the chunk storage
     * @return string
     */
    abstract public function chunksDiskName();
    
    /**
     * The storage path for the chnunks
     * 
     * @return string the full path to the storage
     */
    abstract public function chunksStorageDirectory();

    /**
     * Returns the time stamp string for clear command
     * @return string
     */
    abstract public function clearTimestampString();
}