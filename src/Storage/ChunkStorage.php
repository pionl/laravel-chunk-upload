<?php

namespace Pion\Laravel\ChunkUpload\Storage;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Pion\Laravel\ChunkUpload\ChunkFile;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;

/**
 * Notes:
 * - all chunks are stored in their own directory
 * - final file is moved out of the directory (will be later deleted)
 */
class ChunkStorage
{
    public const CHUNK_EXTENSION = 'part';
    public const UPLOAD_SHARD_LENGTH = 4;
    public const UPLOAD_SEGMENT_LENGTH = 32;

    /**
     * Returns the application instance of the chunk storage.
     *
     * @return ChunkStorage
     */
    public static function storage()
    {
        return app(self::class);
    }

    /**
     * @var AbstractConfig
     */
    protected $config;
    /**
     * The disk that holds the chunk files.
     *
     * @var FilesystemContract|FilesystemAdapter
     */
    protected $disk;
    /**
     * @var Local|LocalFilesystemAdapter
     */
    protected $diskAdapter;
    protected $isLocalDisk;

    /**
     * @var
     */
    protected $usingDeprecatedLaravel;

    /**
     * @param FilesystemAdapter|FilesystemContract $disk   the desired disk for chunk storage
     * @param AbstractConfig                       $config
     */
    public function __construct($disk, $config)
    {
        // save the config
        $this->config = $config;
        $this->usingDeprecatedLaravel = false === class_exists(LocalFilesystemAdapter::class);
        $this->disk = $disk;

        if (false === $this->usingDeprecatedLaravel) {
            // try to get the adapter
            if (!method_exists($this->disk, 'getAdapter')) {
                throw new \RuntimeException('FileSystem driver must have an adapter implemented');
            }

            // get the disk adapter
            $this->diskAdapter = $this->disk->getAdapter();

            // check if its local adapter
            $this->isLocalDisk = $this->diskAdapter instanceof LocalFilesystemAdapter;
        } else {
            $driver = $this->driver();

            // try to get the adapter
            if (!method_exists($driver, 'getAdapter')) {
                throw new \RuntimeException('FileSystem driver must have an adapter implemented');
            }

            // get the disk adapter
            $this->diskAdapter = $driver->getAdapter();

            // check if its local adapter
            $this->isLocalDisk = $this->diskAdapter instanceof Local;
        }
    }

    /**
     * The current path for chunks directory.
     *
     * @return string
     *
     * @throws \RuntimeException when the adapter is not local
     */
    public function getDiskPathPrefix()
    {
        if (true === $this->usingDeprecatedLaravel && $this->isLocalDisk) {
            return $this->diskAdapter->getPathPrefix();
        }

        if ($this->isLocalDisk) {
            return $this->disk->path('');
        }

        throw new \RuntimeException('The full path is not supported on current disk - local adapter supported only');
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
     * Returns the relative directory that stores parts for a single upload.
     *
     * @param string $fileId
     *
     * @return string
     */
    public function directoryForFile(string $fileId)
    {
        return $this->directory().implode('/', $this->directorySegmentsForFile($fileId)).'/';
    }

    /**
     * Returns the relative merged file path for a single upload.
     *
     * @param string $fileId
     *
     * @return string
     */
    public function mergedFilePathForFile(string $fileId)
    {
        $segments = $this->directorySegmentsForFile($fileId);
        $fileName = array_pop($segments).'.'.self::CHUNK_EXTENSION;

        if (empty($segments)) {
            return $this->directory().$fileName;
        }

        return $this->directory().implode('/', $segments).'/'.$fileName;
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

        return $this->rejectNonChunkFiles($filesCollection, $rejectClosure);
    }

    /**
     * Returns an array of files in the given directory.
     *
     * @param string        $directory
     * @param bool          $recursive
     * @param \Closure|null $rejectClosure
     *
     * @return Collection
     */
    public function filesByDirectory(string $directory, bool $recursive = false, $rejectClosure = null)
    {
        $filesCollection = new Collection($this->disk->files($directory, $recursive));

        return $this->rejectNonChunkFiles($filesCollection, $rejectClosure);
    }

    /**
     * Returns the old chunk files.
     *
     * @return Collection<ChunkFile> collection of a ChunkFile objects
     */
    public function oldChunkFiles()
    {
        $files = $this->filesByDirectory($this->directory(), true);
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
     * @return FilesystemOperator|FilesystemInterface
     */
    public function driver()
    {
        return $this->disk()->getDriver();
    }

    /**
     * Deletes empty upload directories up to the chunks root.
     *
     * @param string $directory
     *
     * @return void
     */
    public function deleteEmptyDirectories(string $directory)
    {
        $rootDirectory = rtrim($this->directory(), '/');
        $currentDirectory = trim($directory, '/');

        while ('' !== $currentDirectory && str_starts_with($currentDirectory, $rootDirectory) && $currentDirectory !== $rootDirectory) {
            if (!$this->directoryIsEmpty($currentDirectory)) {
                return;
            }

            $this->disk()->deleteDirectory($currentDirectory);
            $currentDirectory = trim(dirname($currentDirectory), '/');
        }
    }

    /**
     * @param string $fileId
     *
     * @return array<int, string>
     */
    protected function directorySegmentsForFile(string $fileId)
    {
        $sanitizedFileId = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileId);
        $shard = substr($sanitizedFileId, 0, self::UPLOAD_SHARD_LENGTH);
        $remainder = substr($sanitizedFileId, self::UPLOAD_SHARD_LENGTH);
        $segments = ['' === $shard ? '_' : $shard];

        if ('' === $remainder) {
            $segments[] = $segments[0];

            return $segments;
        }

        foreach (str_split($remainder, self::UPLOAD_SEGMENT_LENGTH) as $segment) {
            $segments[] = $segment;
        }

        return $segments;
    }

    /**
     * @param Collection    $filesCollection
     * @param \Closure|null $rejectClosure
     *
     * @return Collection
     */
    protected function rejectNonChunkFiles(Collection $filesCollection, $rejectClosure = null)
    {
        return $filesCollection->reject(function ($file) use ($rejectClosure) {
            $shouldReject = !preg_match('/\.'.self::CHUNK_EXTENSION.'$/', $file);
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
     * @param string $directory
     *
     * @return bool
     */
    protected function directoryIsEmpty(string $directory)
    {
        if (!empty($this->disk()->files($directory))) {
            return false;
        }

        if (method_exists($this->disk(), 'directories') && !empty($this->disk()->directories($directory))) {
            return false;
        }

        return true;
    }
}
