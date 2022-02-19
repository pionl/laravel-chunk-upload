<?php

namespace Pion\Laravel\ChunkUpload\Storage;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\FilesystemInterface;
use Pion\Laravel\ChunkUpload\ChunkFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use RuntimeException;

class ChunkStorage
{
    const CHUNK_EXTENSION = 'part';

    public static function storage()
    {
        return app(self::class);
    }

    protected $config;
    protected $disk;
    protected $diskAdapter;
    protected $isLocalDisk;

    /**
     * ChunkStorage constructor.
     *
     * @param FilesystemAdapter $disk   the desired disk for chunk storage
     * @param AbstractConfig     $config
     */
    public function __construct(FilesystemAdapter $disk, $config)
    {
        // save the config
        $this->config = $config;

        // cache the storage path
        $this->disk = $disk;

        // try to get the adapter
        if (!method_exists($this->disk, 'getAdapter')) {
            throw new RuntimeException('FileSystem driver must have an adapter implemented');
        }

        // get the disk adapter
        $this->diskAdapter = $this->disk->getAdapter();

        // check if its local adapter
        $this->isLocalDisk = $this->diskAdapter instanceof LocalFilesystemAdapter;
    }

    /**
     * The current path for chunks directory.
     *
     * @return string
     *
     * @throws RuntimeException when the adapter is not local
     */
    public function getDiskPathPrefix()
    {
        if ($this->isLocalDisk) {
            return $this->disk->path('');
        }

        throw new RuntimeException('The full path is not supported on current disk - local adapter supported only');
    }

    /**
     * The current chunks directory.
     *
     * @return string
     */
    public function directory()
    {
        return $this->config->chunksStorageDirectory().'/';
    }

    /**
     * Returns an array of files in the chunks directory.
     *
     * @param \Closure|null $rejectClosure
     *
     * @return Collection
     *
     * @see FilesystemAdapter::files()
     * @see AbstractConfig::chunksStorageDirectory()
     */
    public function files($rejectClosure = null)
    {
        // we need to filter files we don't support, lets use the collection
        $filesCollection = new Collection($this->disk->files($this->directory(), false));

        return $filesCollection->reject(function ($file) use ($rejectClosure) {
            // ensure the file ends with allowed extension
            $shouldReject = !preg_match('/.'.self::CHUNK_EXTENSION.'$/', $file);
            if ($shouldReject) {
                return true;
            }
            if (is_callable($rejectClosure)) {
                return $rejectClosure($file);
            }

            return false;
        });
    }

    /**
     * Returns the old chunk files.
     *
     * @return Collection<ChunkFile> collection of a ChunkFile objects
     */
    public function oldChunkFiles()
    {
        $files = $this->files();
        // if there are no files, lets return the empty collection
        if ($files->isEmpty()) {
            return $files;
        }

        // build the timestamp
        $timeToCheck = strtotime($this->config->clearTimestampString());
        $collection = new Collection();

        // filter the collection with files that are not correct chunk file
        // loop all current files and filter them by the time
        $files->each(function ($file) use ($timeToCheck, $collection) {
            // get the last modified time to check if the chunk is not new
            $modified = $this->disk()->lastModified($file);

            // delete only old chunk
            if ($modified < $timeToCheck) {
                $collection->push(new ChunkFile($file, $modified, $this));
            }
        });

        return $collection;
    }

    /**
     * @return AbstractConfig
     */
    public function config()
    {
        return $this->config;
    }

    /**
     * @return FilesystemAdapter
     */
    public function disk()
    {
        return $this->disk;
    }

    /**
     * Returns the driver.
     *
     * @return FilesystemOperator
     */
    public function driver()
    {
        return $this->disk()->getDriver();
    }
}
