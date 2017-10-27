# Laravel chunked upload
Easy to use service for chunked upload with several js providers on top of Laravel's file upload.

[![Total Downloads](https://poser.pugx.org/pion/laravel-chunk-upload/downloads?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![Latest Stable Version](https://poser.pugx.org/pion/laravel-chunk-upload/v/stable?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![Latest Unstable Version](https://poser.pugx.org/pion/laravel-chunk-upload/v/unstable?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![Build Status](https://travis-ci.org/pionl/laravel-chunk-upload.svg?branch=master)](https://travis-ci.org/pionl/laravel-chunk-upload)

* [Installation](#installation)
* [Usage](#usage)
* [Supports](#supports)
* [Features](#features)
* [Basic documentation](#basic-documentation)
* [Example](#example)
    * [laravel-chunk-upload-example](https://github.com/pionl/laravel-chunk-upload-example)
    * [Javascript](#javascript)
    * [Laravel controller](#laravel-controller)
    * [Route](#route)
* [Providers/Handlers](#providers-handlers)
* [Changelog](#changelog)
* [Contribution or overriding](#contribution-or-overriding)
* [Suggested frontend libs](#suggested-frontend-libs)

## Installation

**Install via composer**

```
composer require pion/laravel-chunk-upload
```
    
**Add the service provider**

```php
\Pion\Laravel\ChunkUpload\Providers\ChunkUploadServiceProvider::class
```    

___Optional___

**Publish the config**

Run the publish command to copy the translations (Laravel 5.2 and above)

```
php artisan vendor:publish --provider="Pion\Laravel\ChunkUpload\Providers\ChunkUploadServiceProvider"
```

## Usage

In your own controller create the `FileReceiver`, more in example.

## Supports

* Laravel 5+
* Laravels 5.5 Auto discovery
* [blueimp-file-upload](https://github.com/blueimp/jQuery-File-Upload) - partial support (simple chunked and single upload)
* [Plupload](https://github.com/moxiecode/plupload)
* [resumable.js](https://github.com/23/resumable.js)
* [DropZone](https://gitlab.com/meno/dropzone/)

## Features
* **Chunked uploads**
  uses **chunked writing** aswell to minimize the memory footprint
* **Storing per Laravel Session to prevent overwrite**
  all TMP files are stored with session token
* [**Clear command and schedule**](#uploads:clear)
  the package registers the shedule command (uploads:clear) that will clear all unfinished chunk uploads
* **Automatic handler selection** since `v0.2.4` you can use automatic detection selection the handler
to use from the current supported providers. You can also register your own handler to the automatic detection (more in Handlers)
* Supports cross domain request (must change the config - see Cross domain request section in readme)

## Basic documentation

1. Create a Upload controller. If using Laravel 5.4 and above, add your upload controller into `web` route. If
necessary, add to `api` routes and change the config to use IP for chunk name.
2. Implement your Javascript code (you can use the same code as below or in example repository)
3. __Check if your library is sending `cookie`, the chunk naming uses session (you can [change it](#unique-naming) - will use only IP address)__
4. Implement the FileReceiver (example below)

### FileReceiver
- You must create the file receiver with the file index (in the `Request->file`), the current request and the desired handler class (currently the `ContentRangeUploadHandler::class`)
- If upload has failed (like file size limit, exception is raised)

Then you can use methods:

#### `isUploaded()`
determines if the file object is in the request

#### `receive()`
Tries to handle the upload request. If the file is not uploaded, returns false. If the file
is present in the request, it will create the save object.

If the file in the request is chunk, it will create the `ChunkSave` object, otherwise creates the `SingleSave`
which does nothing at this moment.

## Example 

The full example (Laravel 5.4 - works same on previous versions) can be found in separate repository [laravel-chunk-upload-example](https://github.com/pionl/laravel-chunk-upload-example)
with DropZone and jQuery-File-Upload implementation.
    
### Laravel controller
* Create laravel controller `UploadController` and create the file receiver with the desired handler.
* You must import the full namespace in your controller (`use`).
* When upload is finished, don't forget to **move the file to desired folder (as standard UploadFile implementation)**. 
You can check the example project.
* An example of save function below the handler usage

#### Dynamic handler usage

The correct handler for your JS provider will be selected automatically based on the sent request. This is the easies init.

##### With dependency injection

Build the `FileReceiver` with a first uploaded file in the request.

[Full Controller in example](https://github.com/pionl/laravel-chunk-upload-example/blob/master/app/Http/Controllers/DependencyUploadController.php)

```php 
/**
 * Handles the file upload
 *
 * @param FileReceiver $receiver
 *
 * @return \Illuminate\Http\JsonResponse
 *
 * @throws UploadMissingFileException
 */
public function upload(FileReceiver $receiver) {

    // check if the upload is success
    if ($receiver->isUploaded()) {

        // receive the file
        $save = $receiver->receive();

        // check if the upload has finished (in chunk mode it will send smaller files)
        if ($save->isFinished()) {
            // save the file and return any response you need
            return $this->saveFile($save->getFile());
        } else {
            // we are in chunk mode, lets send the current progress

            /** @var AbstractHandler $handler */
            $handler = $save->handler();

            return response()->json([
                "done" => $handler->getPercentageDone(),
            ]);
        }
    } else {
        throw new UploadMissingFileException();
    }
}
```

##### With own file index

[Full Controller in example](https://github.com/pionl/laravel-chunk-upload-example/blob/master/app/Http/Controllers/UploadController.php)

#### Static handler usage

This will force a given handler to be used for processing the upload.

```php
/**
 * Handles the file upload
 *
 * @param Request $request
 *
 * @return \Illuminate\Http\JsonResponse
 * 
 * @throws UploadMissingFileException
 */
public function upload(Request $request) {

    // Create the file receiver, exception is thrown if file upload is invalid (size limit, etc)
    $receiver = new FileReceiver("file", $request, ContentRangeUploadHandler::class);

    // Check if the upload is success
    if ($receiver->isUploaded()) {

        // receive the file
        $save = $receiver->receive();

        // check if the upload has finished (in chunk mode it will send smaller files)
        if ($save->isFinished()) {
            // save the file and return any response you need
            return $this->saveFile($save->getFile());
        } else {
            // we are in chunk mode, lets send the current progress

            /** @var ContentRangeUploadHandler $handler */
            $handler = $save->handler();
            
            return response()->json([
                "progress" => $handler->getPercentageDone(),
            ]);
        }
    } else {
        throw new UploadMissingFileException();
    }
}
```

#### Usage with multiple files in one Request

```php
/**
 * Handles the file upload
 *
 * @param Request $request
 *
 * @param Int $fileIndex
 *
 * @return \Illuminate\Http\JsonResponse
 * 
 * @throws UploadMissingFileException
 */
public function upload(Request $request) {
    // Response for the files - completed and uncompleted
    $files = [];

    // Get array of files from request
    $files = $request->file('files');
    
    if (!is_array($files)) {
        throw new UploadMissingFileException();
    }

    
    // Loop sent files
    foreach ($files as $file) {
        // Instead of passing the index name, pass the UploadFile object from the $files array we are looping
        // Exception is thrown if file upload is invalid (size limit, etc)
        
        // Create the file receiver via dynamic handler
        $receiver = new FileReceiver($file, $request, HandlerFactory::classFromRequest($request));
        // or via static handler usage
        $receiver = new FileReceiver($file, $request, ContentRangeUploadHandler::class);
        
        if ($receiver->isUploaded()) {
            // receive the file
            $save = $receiver->receive();
    
            // check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
                // save the file and return any response you need
                $files[] = $this->saveFile($save->getFile());
            } else {
                // we are in chunk mode, lets send the current progress
    
                /** @var ContentRangeUploadHandler $handler */
                $handler = $save->handler();
                
                // Add the completed file
                $files[] = [
                    "progress" => $handler->getPercentageDone(),
                    "finished" => false
                ];
            }
        }
    }
    
    return response()->json($files);
}
```

#### Save file after upload

This example moves the file (merged chunks) into own upload directory. This will ensure that the chunk will be deleted after
move.

```php
 /**
 * Saves the file
 *
 * @param UploadedFile $file
 *
 * @return \Illuminate\Http\JsonResponse
 */
protected function saveFile(UploadedFile $file)
{
    $fileName = $this->createFilename($file);
    // Group files by mime type
    $mime = str_replace('/', '-', $file->getMimeType());
    // Group files by the date (week
    $dateFolder = date("Y-m-W");
    // Build the file path
    $filePath = "upload/{$mime}/{$dateFolder}/";
    $finalPath = storage_path("app/".$filePath);
    // move the file name
    $file->move($finalPath, $fileName);
    return response()->json([
        'path' => $filePath,
        'name' => $fileName,
        'mime_type' => $mime
    ]);
}
```
#### Route
Add a route to your controller in the `web` route (if you want to use the session).
```php
Route::post('upload', 'UploadController@upload');
```

### Commands

#### uploads:clear
Clears old chunks from the chunks folder, uses the config to detect which files can be deleted via the last edit time `clear.timestamp`.

The scheduler can be disabled by a config `clear.schedule.enabled` or the cron time can be changed in `clear.schedule.cron` (don't forget to setup your scheduler in the cron)

##### Command usage

````
php artisan uploads:clear
````

### Config

#### Unique naming
In default we use client browser info to generate unique name for the chunk file (support same file upload at same time).
The logic supports also using the `Session::getId()`, but you need to force your JS library to send the cookie. 
You can update the `chunk.name.use` settings for custom usage.

#### Cross domain request
When using uploader for the cross domain request you must setup the `chunk.name.use` to browser logic instead of session.

    "use" => [
        "session" => false, // should the chunk name use the session id? The uploader muset send cookie!,
        "browser" => true // instead of session we can use the ip and browser?
    ]
    
Then setup your laravel [Setup guide](https://github.com/barryvdh/laravel-cors)

## Providers/Handlers
Use `AbstractHandler` for type hint or use a specific handler to se additional methods.

### ContentRangeUploadHandler

* supported by blueimp-file-upload
* uses the Content-range header with the bytes range

##### Additional methods

* `getBytesStart()` - returns the starting bytes for current request
* `getBytesEnd()` - returns the ending bytes for current request
* `getBytesTotal()` - returns the total bytes for the file

### ChunksInRequestUploadHandler
* Supported by plupload
* uses the chunks numbers from the request

### ResumableJSUploadHandler
* Supported by resumable.js
* uses the chunks numbers from the request

### DropZoneUploadHandler
* Supported by DropZone
* uses the chunks numbers from the request

### Using own implementation

See the `Contribution` section in Readme

### Automatic handler - `HandlerFactory`

You can use the automatic detection of the correct handler (provider) by using the `HandlerFactory::classFromRequest` as
a third parameter when constructing the `FileReceiver`.
 
```php
// Exception is thrown if file upload is invalid (size limit, etc)
$receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));
```
#### Fallback class
The default fallback class is stored in the HandlerFactory (default `SingleUploadHandler::class`). 

You can change it globally by calling 
```php
HandlerFactory::setFallbackHandler(CustomHandler::class)
```     
or pass as second parameter when using 
 
```php
HandlerFactory::classFromRequest($request, CustomHandler::class)
```

## Changelog

### Since 1.1.3
* Added DropZone support (#22)
* Removed Laravel dependency in favor of Illuminate packages (#21)

### Since 1.1.2
* Added support for  Auto-Discovery (thanks to @laravelish - [#20](https://github.com/pionl/laravel-chunk-upload/pull/20))

### Since 1.1.1
* Added support for Laravel 5.5 (thanks to @Colbydude - [#18](https://github.com/pionl/laravel-chunk-upload/pull/18))

### Since 1.1.0
* If there is an error while upload, exception will be thrown on init.

### Since 1.0.3
* Enabled to construct the `FileReceiver` with dependency injection - the fasted way.
* Removed the `getChunkFile` and added `getUploadedFile` for all Save classes. Returns always the uploaded file (the uploaded chunk). 

### Since 1.0.2
* Added resumable.js
* Added `getChunkFile` method in `ChunkSave` for returning only the chunk file 

### Since 1.0.1
* Added support for passing file object instead of fileIndex (example: multiple files in a request). Change discussion in #7 (@RAZORzdenko), merged in #8

### Since 1.0.0

* Updated composer to support Laravel 5.4

### Since v0.3

* Support for cross domain requests (only chunk naming)
* Added support for [plupload package](https://github.com/moxiecode/plupload)
* Added automatic handler selection based on the request

### Since v0.2.0

The package supports the Laravel Filesystem. Because of this, the storage must be withing the app folder `storage/app/` or custom drive (only local) - can be set in the config `storage.disk`.

The cloud drive is not supported because of the chunked write (probably could be changed to use a stream) and the resulting object - `UploadedFile` that supports only full path.

## Todo

- [ ] add more providers
- [ ] add facade for a quick usage with callback and custom response based on the handler
- [ ] add support to different drive than a local drive
- [ ] Add unit test coverage (interested to help? Fork the project)

## Contribution or overriding
See [CONTRIBUTING.md](CONTRIBUTING.md) for how to contribute changes. All contributions are welcome.

## Copyright and License

[laravel-chunk-upload](https://github.com/pionl/laravel-chunk-upload)
was written by [Martin Kluska](http://kluska.cz) and is released under the 
[MIT License](LICENSE.md).

Copyright (c) 2016 Martin Kluska
