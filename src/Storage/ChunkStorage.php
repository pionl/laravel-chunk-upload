<?php
namespace Pion\Laravel\ChunkUpload\Storage;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FilesystemInterface;
use Pion\Laravel\ChunkUpload\ChunkFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;

class ChunkStorage
{
    const CHUNK_EXTENSION = "part";

    /**
     * Returns the application instance of the chunk storage
     * @return ChunkStorage
     */
    public static function storage() {
        return app(self::class);
    }

    /**
     * @var AbstractConfig
     */
    protected $config;

    /**
     * The disk that holds the chunk files
     *
     * @var FilesystemAdapter
     */
    protected $disk;

    /**
     * @var Local
     */
    protected $diskAdapter;

    /**
     * Is provided disk a local drive
     * @var bool
     */
    protected $isLocalDisk;


    /**
     * ChunkStorage constructor.
     *
     * @param FilesystemAdapter   $disk the desired disk for chunk storage
     * @param AbstractConfig $config
     */
    public function __construct(FilesystemAdapter $disk, $config)
    {
        // save the config
        $this->config = $config;

        // cache the storage path
        $this->disk = $disk;


        $driver = $this->driver();

        // try to get the adapter
        if (!method_exists($driver, "getAdapter")) {
            throw new \RuntimeException("FileSystem driver must have an adapter implemented");
        }

        // get the disk adapter
        $this->diskAdapter = $driver->getAdapter();

        // check if its local adapter
        $this->isLocalDisk = $this->diskAdapter instanceof Local;
    }

    /**
     * The current path for chunks directory
     *
     * @return string
     *
     * @throws RuntimeException when the adapter is not local
     */
    public function getDiskPathPrefix()
    {
        if ($this->isLocalDisk) {
            return $this->diskAdapter->getPathPrefix();
        }

        throw new \RuntimeException("The full path is not supported on current disk - local adapter supported only");
    }

    /**
     * The current chunks directory
     * @return string
     */
    public function directory()
    {
        return $this->config->chunksStorageDirectory()."/";
    }

    /**
     * Returns an array of files in the chunks directory
     * 
     * @return array
     *
     * @see FilesystemAdapter::files()
     * @see AbstractConfig::chunksStorageDirectory()
     */
    public function files()
    {
        return $this->disk->files($this->directory(), false);
    }

    /**
     * Returns the old chunk files
     *
     * @return Collection<ChunkFile> collection of a ChunkFile objects
     */
    public function oldChunkFiles()
    {
        $collection = new Collection();
        $files = $this->files();

        // if there are no files, lets return the empty collection
        if (empty($files)) {
            return $collection;
        }

        // build the timestamp
        $timeToCheck = strtotime($this->config->clearTimestampString());

        // we need to filter files we dont support, lets use the collection
        $filesCollection = new Collection($files);

        // filter the collection with files that are not correct chunk file
        $filesCollection->reject(function ($file) {
            // ensure the file ends with allowed extension
            return !preg_match("/.".self::CHUNK_EXTENSION."$/", $file);
            
        })// loop all current files and filter them by the time
        ->each(function ($file) use ($timeToCheck, $collection) {

            // get the last modifed time to check if the chunk is not new
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
     * Returns the driver
     *
     * @return FilesystemInterface
     */
    public function driver()
    {
        return $this->disk()->getDriver();
    }
}