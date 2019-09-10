<?php

namespace Pion\Laravel\ChunkUpload\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Pion\Laravel\ChunkUpload\Commands\ClearChunksCommand;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ChunkUploadServiceProvider extends ServiceProvider
{
    /**
     * When the service is being booted.
     */
    public function boot()
    {
        // Get the schedule config
        $config = $this->app->make(AbstractConfig::class);
        $scheduleConfig = $config->scheduleConfig();

        // Run only if schedule is enabled
        if (true === Arr::get($scheduleConfig, 'enabled', false)) {
            // Wait until the app is fully booted
            $this->app->booted(function () use ($scheduleConfig) {
                // Get the scheduler instance
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);

                // Register the clear chunks with custom schedule
                $schedule->command('uploads:clear')
                    ->cron(Arr::get($scheduleConfig, 'cron', '* * * * *'));
            });
        }

        $this->registerHandlers($config->handlers());
    }

    /**
     * Register the package requirements.
     *
     * @see ChunkUploadServiceProvider::registerConfig()
     */
    public function register()
    {
        // Register the commands
        $this->commands([
            ClearChunksCommand::class,
        ]);

        // Register the config
        $this->registerConfig();

        // Register the config via abstract instance
        $this->app->singleton(AbstractConfig::class, function () {
            return new FileConfig();
        });

        // Register the config via abstract instance
        $this->app->singleton(ChunkStorage::class, function ($app) {
            /** @var AbstractConfig $config */
            $config = $app->make(AbstractConfig::class);

            // Build the chunk storage
            return new ChunkStorage($this->disk($config->chunksDiskName()), $config);
        });

        /*
         * Bind a FileReceiver for dependency and use only the first object
         */
        $this->app->bind(FileReceiver::class, function ($app) {
            /** @var Request $request */
            $request = $app->make('request');

            // Get the first file object - must be converted instances of UploadedFile
            $file = Arr::first($request->allFiles());

            // Build the file receiver
            return new FileReceiver($file, $request, HandlerFactory::classFromRequest($request));
        });
    }

    /**
     * Returns disk name.
     *
     * @param string $diskName
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function disk($diskName)
    {
        return Storage::disk($diskName);
    }

    /**
     * Publishes and mergers the config. Uses the FileConfig. Registers custom handlers.
     *
     * @see FileConfig
     * @see ServiceProvider::publishes
     * @see ServiceProvider::mergeConfigFrom
     *
     * @return $this
     */
    protected function registerConfig()
    {
        // Config options
        $configIndex = FileConfig::FILE_NAME;
        $configFileName = FileConfig::FILE_NAME.'.php';
        $configPath = __DIR__.'/../../config/'.$configFileName;

        // Publish the config
        $this->publishes([
            $configPath => config_path($configFileName),
        ]);

        // Merge the default config to prevent any crash or unfilled configs
        $this->mergeConfigFrom(
            $configPath,
            $configIndex
        );

        return $this;
    }

    /**
     * Registers handlers from config.
     *
     * @param array $handlersConfig
     *
     * @return $this
     */
    protected function registerHandlers(array $handlersConfig)
    {
        $overrideHandlers = Arr::get($handlersConfig, 'override', []);
        if (count($overrideHandlers) > 0) {
            HandlerFactory::setHandlers($overrideHandlers);

            return $this;
        }

        foreach (Arr::get($handlersConfig, 'custom', []) as $handler) {
            HandlerFactory::register($handler);
        }

        return $this;
    }
}
