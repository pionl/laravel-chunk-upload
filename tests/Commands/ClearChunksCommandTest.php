<?php

namespace Tests\Commands;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Pion\Laravel\ChunkUpload\Commands\ClearChunksCommand;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;
use Symfony\Component\Console\Tester\CommandTester;

class ClearChunksCommandTest extends TestCase
{
    /**
     * @var Container
     */
    private $app;

    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var ChunkStorage
     */
    private $storage;

    /**
     * @var string
     */
    private $packageRoot;

    /**
     * @var string
     */
    private $testRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packageRoot = dirname(__DIR__, 2);
        $this->testRoot = $this->packageRoot.'/test';
        $this->files = new Filesystem();

        $this->files->deleteDirectory($this->testRoot);
        $this->files->makeDirectory($this->testRoot, 0755, true);

        $this->app = new TestCommandContainer();
        Container::setInstance($this->app);
        $this->app->instance('app', $this->app);
        $this->app->instance('files', $this->files);
        $this->app->instance('config', new Repository([
            'filesystems.default' => 'chunk-test',
            'filesystems.disks.chunk-test' => [
                'driver' => 'local',
                'root' => $this->packageRoot,
            ],
            'chunk-upload.storage.disk' => 'chunk-test',
            'chunk-upload.storage.chunks' => 'test/chunks',
            'chunk-upload.clear.timestamp' => '-3 HOURS',
            'chunk-upload.clear.schedule' => [
                'enabled' => false,
            ],
            'chunk-upload.handlers' => [],
        ]));

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);

        $filesystemManager = new FilesystemManager($this->app);
        $this->app->instance('filesystem', $filesystemManager);

        $this->storage = new ChunkStorage($filesystemManager->disk('chunk-test'), new FileConfig());
        $this->app->instance(ChunkStorage::class, $this->storage);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->testRoot);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testCommandDeletesOnlyOldPartsAndCleansTheirEmptyDirectories()
    {
        $command = new ClearChunksCommand();
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);

        $now = time();
        $old = $now - 4 * 3600;
        $fixture = [
            'chunks/' => null,
            'chunks/dead/uploadone/' => null,
            'chunks/dead/uploadone/expired.part' => $old,
            'chunks/dead/' => null,
            'chunks/fres/huploadnow/' => null,
            'chunks/fres/huploadnow/recent.part' => $now,
            'chunks/fres/huploadnow/recent.txt' => $now,
            'chunks/manual-empty/inner-empty/' => null,
            'chunks/stal/ekeepdir/' => null,
            'chunks/stal/ekeepdir/expired.part' => $old,
            'chunks/stal/ekeepdir/keep.txt' => $old,
            'chunks/standalone-empty/' => null,
        ];

        $this->createTree($fixture);

        $exitCode = $tester->execute([]);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Chunks: cleared 2 files', $tester->getDisplay());
        $this->assertEquals([
            'chunks/',
            'chunks/fres/',
            'chunks/fres/huploadnow/',
            'chunks/fres/huploadnow/recent.part',
            'chunks/fres/huploadnow/recent.txt',
            'chunks/manual-empty/',
            'chunks/manual-empty/inner-empty/',
            'chunks/stal/',
            'chunks/stal/ekeepdir/',
            'chunks/stal/ekeepdir/keep.txt',
            'chunks/standalone-empty/',
        ], $this->tree($this->testRoot));
    }

    /**
     * @param int $modifiedAt
     *
     * @return void
     */
    private function createTree(array $entries)
    {
        foreach ($entries as $entry => $modifiedAt) {
            $relativePath = 'test/'.$entry;
            $absolutePath = $this->packageRoot.'/'.$relativePath;

            if (null === $modifiedAt) {
                if (!$this->files->isDirectory($absolutePath)) {
                    $this->files->makeDirectory($absolutePath, 0755, true);
                }

                continue;
            }

            $directory = dirname($absolutePath);

            if (!$this->files->isDirectory($directory)) {
                $this->files->makeDirectory($directory, 0755, true);
            }

            file_put_contents($absolutePath, $relativePath);
            touch($absolutePath, $modifiedAt);
        }
    }

    /**
     * @param string $directory
     * @param string $prefix
     *
     * @return array<int, string>
     */
    private function tree($directory, $prefix = '')
    {
        $entries = array_values(array_diff(scandir($directory), ['.', '..']));
        $tree = [];

        foreach ($entries as $entry) {
            $fullPath = $directory.'/'.$entry;
            $relativePath = '' === $prefix ? $entry : $prefix.'/'.$entry;

            if (is_dir($fullPath)) {
                $tree[] = $relativePath.'/';
                $tree = array_merge($tree, $this->tree($fullPath, $relativePath));
                continue;
            }

            $tree[] = $relativePath;
        }

        return $tree;
    }
}

class TestCommandContainer extends Container
{
    public function runningUnitTests()
    {
        return true;
    }

    public function environment(...$environments)
    {
        if (empty($environments)) {
            return 'testing';
        }

        return in_array('testing', $environments, true);
    }
}
