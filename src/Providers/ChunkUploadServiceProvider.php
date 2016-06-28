<?php
namespace Pion\Laravel\ChunkUpload\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Pion\Laravel\ChunkUpload\Commands\ClearChunksCommand;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Config\FileConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

class ChunkUploadServiceProvider extends ServiceProvider
{
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