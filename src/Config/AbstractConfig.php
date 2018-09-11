<?php

namespace Pion\Laravel\ChunkUpload\Config;

abstract class AbstractConfig
{
    /**
     * Returns the config from the application container.
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
     * Returns a list custom handlers (custom, override).
     *
     * @return array
     */
    abstract public function handlers();

    /**
     * Returns the disk name to use for the chunk storage.
     *
     * @return string
     */
    abstract public function chunksDiskName();

    /**
     * The storage path for the chunks.
     *
     * @return string the full path to the storage
     */
    abstract public function chunksStorageDirectory();

    /**
     * Returns the time stamp string for clear command.
     *
     * @return string
     */
    abstract public function clearTimestampString();

    /**
     * Returns the schedule config array.
     *
     * @return array<enable,cron>
     */
    abstract public function scheduleConfig();

    /**
     * Should the chunk name add a session?
     *
     * @return bool
     */
    abstract public function chunkUseSessionForName();

    /**
     * Should the chunk name add a ip address?
     *
     * @return bool
     */
    abstract public function chunkUseBrowserInfoForName();
}
