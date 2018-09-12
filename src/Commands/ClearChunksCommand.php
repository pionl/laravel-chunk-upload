<?php

namespace Pion\Laravel\ChunkUpload\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Pion\Laravel\ChunkUpload\ChunkFile;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;
use Symfony\Component\Console\Output\OutputInterface;

class ClearChunksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears the chunks upload directory. Deletes only .part objects.';

    /**
     * Clears the chunks upload directory.
     *
     * @param ChunkStorage $storage injected chunk storage
     */
    public function handle(ChunkStorage $storage)
    {
        $verbouse = OutputInterface::VERBOSITY_VERBOSE;

        // try to get the old chunk files
        $oldFiles = $storage->oldChunkFiles();

        if ($oldFiles->isEmpty()) {
            $this->warn('Chunks: no old files');

            return;
        }

        $this->info(sprintf('Found %d chunk files', $oldFiles->count()), $verbouse);
        $deleted = 0;

        /** @var ChunkFile $file */
        foreach ($oldFiles as $file) {
            // debug the file info
            $this->comment('> '.$file, $verbouse);

            // delete the file
            if ($file->delete()) {
                ++$deleted;
            } else {
                $this->error('> chunk not deleted: '.$file);
            }
        }

        $this->info('Chunks: cleared '.$deleted.' '.Str::plural('file', $deleted));
    }
}
