<?php

namespace Tests\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Mockery;
use Mockery\Mock;
use Pion\Laravel\ChunkUpload\Commands\ClearChunksCommand;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Handler\ChunksInRequestSimpleUploadHandler;
use Pion\Laravel\ChunkUpload\Handler\ChunksInRequestUploadHandler;
use Pion\Laravel\ChunkUpload\Handler\ContentRangeUploadHandler;
use Pion\Laravel\ChunkUpload\Handler\DropZoneUploadHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Handler\NgFileUploadHandler;
use Pion\Laravel\ChunkUpload\Handler\ResumableJSUploadHandler;
use Pion\Laravel\ChunkUpload\Handler\SingleUploadHandler;
use Pion\Laravel\ChunkUpload\Providers\ChunkUploadServiceProvider;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;
use Tests\FileSystemDriverMock;

class ChunkUploadServiceProviderMockTest extends Mockery\Adapter\Phpunit\MockeryTestCase
{
    /**
     * @var Mock|Container
     */
    protected $app;
    /**
     * @var ChunkUploadServiceProvider|Mock
     */
    protected $service;
    /**
     * @var Mock
     */
    protected $config;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = Mockery::mock(\Illuminate\Contracts\Container\Container::class);
        $this->config = Mockery::mock(Repository::class);

        $this->app->shouldReceive('make')
            ->with(AbstractConfig::class)
            ->andReturn($this->config);

        $this->service = Mockery::mock(ChunkUploadServiceProvider::class, [$this->app])->makePartial();
        $this->service->shouldAllowMockingProtectedMethods();
    }

    public function testBootWithEmptyScheduleAndRegisterEmptyHandlers()
    {
        $this->app->shouldNotReceive('booted');
        $this->config->shouldReceive('scheduleConfig')
            ->once()
            ->andReturn([]);

        $this->config->shouldReceive('handlers')
            ->once()
            ->andReturn([]);

        $this->service->boot();

        $this->assertEquals([
            ContentRangeUploadHandler::class,
            ChunksInRequestUploadHandler::class,
            ResumableJSUploadHandler::class,
            DropZoneUploadHandler::class,
            ChunksInRequestSimpleUploadHandler::class,
            NgFileUploadHandler::class,
        ], HandlerFactory::getHandlers());
    }

    public function testBootWithEmptyScheduleAndCustomHandler()
    {
        $this->app->shouldNotReceive('booted');
        $this->config->shouldReceive('scheduleConfig')
            ->once()
            ->andReturn([]);

        $this->config->shouldReceive('handlers')
            ->once()
            ->andReturn([
                'custom' => [
                    SingleUploadHandler::class,
                    SingleUploadHandler::class,
                ],
            ]);

        $this->service->boot();

        $this->assertEquals([
            ContentRangeUploadHandler::class,
            ChunksInRequestUploadHandler::class,
            ResumableJSUploadHandler::class,
            DropZoneUploadHandler::class,
            ChunksInRequestSimpleUploadHandler::class,
            NgFileUploadHandler::class,
            SingleUploadHandler::class,
            SingleUploadHandler::class,
        ], HandlerFactory::getHandlers());
    }

    public function testBootWithEmptyScheduleAndOverrideHandler()
    {
        $this->app->shouldNotReceive('booted');
        $this->config->shouldReceive('scheduleConfig')
            ->once()
            ->andReturn([]);

        $this->config->shouldReceive('handlers')
            ->once()
            ->andReturn([
                'override' => [
                    ContentRangeUploadHandler::class,
                ],
            ]);

        $this->service->boot();

        $this->assertEquals([
            ContentRangeUploadHandler::class,
        ], HandlerFactory::getHandlers());
    }

    public function testBootScheduleDisabled()
    {
        $this->app->shouldNotReceive('booted');
        $this->config->shouldReceive('scheduleConfig')
            ->once()
            ->andReturn([
                'enabled' => false,
            ]);

        $this->config->shouldReceive('handlers')
            ->once()
            ->andReturn([]);

        $this->service->boot();
    }

    public function testBootScheduleEnabledAndBootWithoutCron()
    {
        $scheduleConfig = [
            'enabled' => true,
        ];
        $scheduleMock = Mockery::mock();
        $scheduleMock->shouldReceive('cron')
            ->once()
            ->with('* * * * *');

        $scheduleMock->shouldReceive('command')
            ->once()
            ->with('uploads:clear')
            ->andReturn($scheduleMock);

        $this->app->shouldReceive('make')
            ->once()
            ->with(Schedule::class)
            ->andReturn($scheduleMock);

        $this->app->shouldReceive('booted')
            ->once()
            ->withArgs(function ($callback) {
                $callback();

                return true;
            });
        $this->config->shouldReceive('scheduleConfig')
            ->once()
            ->andReturn($scheduleConfig);
        $this->config->shouldReceive('handlers')
            ->once()
            ->andReturn([]);

        $this->service->boot();
    }

    public function testBootScheduleEnabledAndBootWithCronSettings()
    {
        $scheduleConfig = [
            'enabled' => true,
            'cron' => '10 * * * *',
        ];
        $scheduleMock = Mockery::mock();
        $scheduleMock->shouldReceive('cron')
            ->once()
            ->with('10 * * * *');

        $scheduleMock->shouldReceive('command')
            ->once()
            ->with('uploads:clear')
            ->andReturn($scheduleMock);

        $this->app->shouldReceive('make')
            ->once()
            ->with(Schedule::class)
            ->andReturn($scheduleMock);

        $this->app->shouldReceive('booted')
            ->once()
            ->withArgs(function ($callback) {
                $callback();

                return true;
            });
        $this->config->shouldReceive('scheduleConfig')
            ->once()
            ->andReturn($scheduleConfig);
        $this->config->shouldReceive('handlers')
            ->once()
            ->andReturn([]);

        $this->service->boot();
    }

    public function testRegister()
    {
        $this->service->shouldReceive('commands')
            ->once()
            ->with([ClearChunksCommand::class]);

        $this->service->shouldReceive('registerConfig')
            ->once()
            ->andReturn($this->service);

        $this->app->shouldReceive('singleton')
            ->withArgs(function ($class, $closure) {
                if (AbstractConfig::class === $class) {
                    $chunkStorage = $closure($this->app);
                    $this->assertInstanceOf(FileConfig::class, $chunkStorage);

                    return true;
                }
                if (ChunkStorage::class === $class) {
                    $this->config->shouldReceive('chunksDiskName')
                        ->once()
                        ->andReturn('local');

                    $fileSystemMock = Mockery::mock(FilesystemContract::class);
                    $fileSystemMock->shouldReceive('getDriver')
                        ->once()
                        ->andReturn(new FileSystemDriverMock());

                    // Force different file mock
                    $this->service->shouldReceive('disk')
                        ->with('local')
                        ->once()
                        ->andReturn($fileSystemMock);

                    $chunkStorage = $closure($this->app);
                    $this->assertInstanceOf(ChunkStorage::class, $chunkStorage);

                    return true;
                }

                return false;
            })
            ->twice();

        $this->app->shouldReceive('bind')
            ->withArgs(function ($class, $closure) {
                if (FileReceiver::class === $class) {
                    $this->assertTrue(is_callable($closure));

                    return true;
                }

                return false;
            })
            ->once();

        $this->service->register();
    }
}
