# Laravel chunked upload
Easy to use service for chunked upload with several js providers on top of Laravel's file upload.

[![Total Downloads](https://poser.pugx.org/pion/laravel-chunk-upload/downloads?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![Latest Stable Version](https://poser.pugx.org/pion/laravel-chunk-upload/v/stable?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![Latest Unstable Version](https://poser.pugx.org/pion/laravel-chunk-upload/v/unstable?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)

* [Installation](#installation)
* [Usage](#usage)
* [Supports](#supports)
* [Features](#features)
* [Basic documentation](#basic-documentation)
* [Example](#example)
    * [laravel-chunk-upload-example](https://github.com/pionl/laravel-chunk-upload-example)
    * [Javascript](#javascript)
    * [Laravel controller](#laravel.controller)
    * [Controller](#controller)
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
* [blueimp-file-upload](https://github.com/blueimp/jQuery-File-Upload) - partial support (simple chunked and single upload)
* [Plupload](https://github.com/moxiecode/plupload)

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

### FileReceiver
You must create the file receiver with the file index (in the `Request->file`), the current request and the desired handler class (currently the `ContentRangeUploadHandler::class`)

Then you can use methods:

#### `isUploaded()`
determines if the file object is in the request

####`receive()`
Tries to handle the upload request. If the file is not uploaded, returns false. If the file
is present in the request, it will create the save object.

If the file in the request is chunk, it will create the `ChunkSave` object, otherwise creates the `SingleSave`
which doesnt nothing at this moment.

## Example

The full example (Laravel 5.4 - works same on previous versions) can be found in separate repo: [laravel-chunk-upload-example](https://github.com/pionl/laravel-chunk-upload-example)

### Javascript

Written for [jQuery-File-Upload](https://github.com/blueimp/jQuery-File-Upload)

```javascript
$element.fileupload({
        url: "upload_url",
        maxChunkSize: 1000000,
        method: "POST",
        sequentialUploads: true,
        formData: function(form) {
            //laravel token for communication
            return [{name: "_token", value: $form.find("[name=_token]").val()}];
        },
        progressall: function(e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            console.log(progress+"%");
        }
    })
        .bind('fileuploadchunksend', function (e, data) {
            //console.log("fileuploadchunksend");
        })
        .bind('fileuploadchunkdone', function (e, data) {
            //console.log("fileuploadchunkdone");
        })
        .bind('fileuploadchunkfail', function (e, data) {
            console.log("fileuploadchunkfail")
        });
```
    
### Laravel controller
Create laravel controller `UploadController` and create the file receiver with the desired handler.

#### Controller
You must import the full namespace in your controler (`use`).

##### Dynamic handler usage

When you support multiple upload providers. [Full Controller in example](https://github.com/pionl/laravel-chunk-upload-example/blob/master/app/Http/Controllers/UploadController.php)

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
    // create the file receiver
    $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

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

##### Static handler usage

We set the handler we want to use always.

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

    // create the file receiver
    $receiver = new FileReceiver("file", $request, ContentRangeUploadHandler::class);

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

            /** @var ContentRangeUploadHandler $handler */
            $handler = $save->handler();
            
            return response()->json([
                "start" => $handler->getBytesStart(),
                "end" => $handler->getBytesEnd(),
                "total" => $handler->getBytesTotal()
            ]);
        }
    } else {
        throw new UploadMissingFileException();
    }
}
```

##### Usage with multiple files in one Request

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
public function upload(Request $request, $fileIndex) {

		// Get array of files from request
		$file = $request->file('file');

    if (!is_null($fileIndex) && is_array($file)) {
      // When not found in array, will return null
      $file = array_get($file, $fileIndex);
    }

		// create the file receiver with handler of your choice
    $receiver = new FileReceiver($file, $request, ContentRangeUploadHandler::class);

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

            /** @var ContentRangeUploadHandler $handler */
            $handler = $save->handler();
            
            return response()->json([
                "start" => $handler->getBytesStart(),
                "end" => $handler->getBytesEnd(),
                "total" => $handler->getBytesTotal()
            ]);
        }
    } else {
        throw new UploadMissingFileException();
    }
}
```

#### Route
Add a route to your controller

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

##### Aditional methods

* `getBytesStart()` - returns the starting bytes for current request
* `getBytesEnd()` - returns the ending bytes for current request
* `getBytesTotal()` - returns the total bytes for the file

### ChunksInRequestUploadHandler
* suppored by plupload
* uses the chunks numbers from the request

### Using own implementation

See the `Contribution` section in Readme

### Automatic handler - `HandlerFactory`

You can use the automatic detection of the correct handler (provider) by using the `HandlerFactory::classFromRequest` as
a third parameter when constructing the `FileReceiver`.
 
```php
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

### Since v0.3

* Support for cross domain requests (only chunk naming)
* Added support for [plupload package](https://github.com/moxiecode/plupload)
* Added automatic handler selection based on the request

### Since v0.2.0

The package supports the Laravel Filesystem. Becouse of this, the storage must be withing the app folder `storage/app/` or custom drive (only local) - can be set in the config `storage.disk`.

The cloud drive is not supported becouse of the chunked write (probably could be changed to use a stream) and the resulting object - `UploadedFile` that supports only full path.

## Todo

- [ ] add more providers
- [ ] add facade for a quick usage with callback and custom response based on the handler
- [x] cron to delete uncompleted files `since v0.2.0`
- [x] file per session (to support multiple) `since v0.1.1`
- [x] add a config with custom storage location `since v0.2.0`
- [ ] add an example project
- [ ] add support to different drive than a local drive

## Contribution or overriding
See [CONTRIBUTING.md](CONTRIBUTING.md) for how to contribute changes. All contributions are welcome.

# Suggested frontend libs

* https://github.com/lemonCMS/react-plupload
* https://github.com/moxiecode/plupload
* https://github.com/blueimp/jQuery-File-Upload

## Copyright and License

[laravel-chunk-upload](https://github.com/pionl/laravel-chunk-upload)
was written by [Martin Kluska](http://kluska.cz) and is released under the 
[MIT License](LICENSE.md).

Copyright (c) 2016 Martin Kluska