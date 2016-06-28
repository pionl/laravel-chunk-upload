<?php
namespace Pion\Laravel\ChunkUpload\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Pion\Laravel\ChunkUpload\Commands\ClearChunksCommand;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ChunkUploadServiceProvider extends ServiceProvider
{

    /**
     * When the service is beeing booted
     */
    public function boot()
    {
        // get the schedule config
        $schedule = AbstractConfig::config()->scheduleConfig();

        // run only if schedule is enabled
        if (Arr::get($schedule, "enabled", false) === true) {

            // wait until the app is fully booted
            $this->app->booted(function () use ($schedule) {
                // get the sheduler
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);

                // register the clear chunks with custom schedule
                $schedule->command('uploads:clear')->cron(Arr::get($schedule, "cron", "* * * * *"));
            });
        }
    }


    /**
     * Register the package requirements.
     *
     * @see ChunkUploadServiceProvider::registerConfig()
     */
    public function register()
    {  
        // register the commands
        $this->commands([
            ClearChunksCommand::class
        ]);

        // register the config
        $this->registerConfig();

        // register the config via abstract instance
        $this->app->singleton(AbstractConfig::class, function () {
            return new FileConfig();
        });

        // register the config via abstract instance
        $this->app->singleton(ChunkStorage::class, function (Application $app) {
            /** @var AbstractConfig $config */
            $config = $app->make(AbstractConfig::class);
            
            // build the chunk storage
            return new ChunkStorage(\Storage::disk($config->chunksDiskName()), $config);
        });
    }

    /**
     * Publishes and mergers the config. Uses the FileConfig
     *
     * @see FileConfig
     * @see ServiceProvider::publishes
     * @see ServiceProvider::mergeConfigFrom
     */
    protected function registerConfig()
    {
        // config options
        $configIndex = FileConfig::FILE_NAME;
        $configFileName = FileConfig::FILE_NAME.".php";
        $configPath = __DIR__.'/../../config/'.$configFileName;

        // publish the config
        $this->publishes([
            $configPath => config_path($configFileName.'.php'),
        ]);

        // merge the default config to prevent any crash or unfiled configs
        $this->mergeConfigFrom(
            $configPath, $configIndex
        );
    }

}