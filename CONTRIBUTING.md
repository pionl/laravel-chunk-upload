# Contribution or overriding

Are welcome. To add a new provider just add a new Handler (which extends AbstractHandler). Then implement the chunk
upload and progress.

1. Fork the project.
2. Create your bugfix/feature branch and write your (try well-commented) code.
3. Commit your changes (and your tests) and push to your branch.
4. Create a new pull request against this package's `master` branch.
5. **Test your code in [laravel-chunk-upload-example](https://github.com/pionl/laravel-chunk-upload-example)**.

## Pull Requests

- **Use the [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md).**
  The easiest way to apply the conventions is to use `composer run lint:fix`.

- **Consider our release cycle.**  We try to follow [SemVer v2.0.0](http://semver.org/). 

- **Document any change in behaviour.**  Make sure the `README.md` and any other relevant 
  documentation are kept up-to-date.

- **Create feature branches.**  Don't ask us to pull from your master branch.

- **One pull request per feature.**  If you want to do more than one thing, send multiple pull requests.

### Before pull-request do:

1. Rebase your changes on master branch
2. Lint project `composer run lint`
3. Run tests `composer run test`
4. (recommended) Write tests
5. (optinal) Rebase your commits to fewer commits
  
**Thank you!**

# Handler class
The basic handler `AbstractHandler` allows to implement own detection of the chunk mode and file naming. Stored in the Handler namespace but you can
store your handler at any namespace (you need to pass the class to the `FileReceiver` as a parameter)

### You must implement:

- `getChunkFileName()` - Returns the chunk file name for a storing the tmp file
- `isFirstChunk()` - Checks if the request has first chunk
- `isLastChunk()` - Checks if the current request has the last chunk
- `isChunkedUpload()` - Checks if the current request is chunked upload
- `getPercentageDone()` - Calculates the current uploaded percentage

### Automatic detection
To enable your own detection, just overide the `canBeUsedForRequest` method

```php
public static function canBeUsedForRequest(Request $request)
{
    return true;
}
```

# Fork
Edit the `HandlerFactory` and add your handler to the `$handlers` array

# At runtime or without forking
Call the `HandlerFactory::register($name)` to register your own Handler at runtime and use it
