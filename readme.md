# Laravel chunked upload

___Project in progress___ 

## Instalation

    composer require pion/laravel-chunk-upload
    
## Usage

In your own controller create the `FileReceiver`, more in example

## Supports

* Laravel 5+
* [blueimp-file-upload](https://github.com/blueimp/jQuery-File-Upload) - partial support (simple chunked and single upload)

## Example

### Javascript

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
#### Route
Add a route to your controller

```php
Route::post('upload', 'UploadController@upload');
```

## Todo

- [ ] add more providers (like pbupload)
- [ ] add facade for a quick usage with callback and custom response based on the handler
- [ ] cron to delete uncompleted files
- [ ] file per session (to support multiple)
- [ ] add a config with custom storage location 

## Contribution
Are welcome. To add a new provider, just add a new Handler (which extends AbstractHandler), implement the chunk
upload and progress