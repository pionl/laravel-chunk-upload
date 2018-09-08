<?php
/**
 *
 */

namespace Pion\Laravel\ChunkUpload\Handler;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Config\AbstractConfig;
use Pion\Laravel\ChunkUpload\Storage\ChunkStorage;

/**
 * Class ResumableJSCheckHandler
 *
 * Check handler that tells if the requested chunk is already uploaded or not
 *
 * Works with:
 * - resumable.js: https://github.com/23/resumable.js
 *
 * @package Pion\Laravel\ChunkUpload\Handler
 */
class ResumableJSCheckHandler extends ChunkCheckHandler
{
    const CHUNK_UUID_INDEX = 'resumableIdentifier';

    const CHUNK_NUMBER_INDEX = 'resumableChunkNumber';

    const FILENAME_INDEX = 'resumableFilename';
}