# Contribution Guidelines

- [Pull Requests](#pull-requests)
- [Adding new library](#adding-new-library)
- [Your additions to your code base](#your-additions-to-your-code-base)

We welcome contributions to our project. If you want to add a new provider, follow these steps:

1. **Fork the Project:** Begin by forking the project to your own GitHub account.
2. **Create a Feature Branch:** Create a new branch for your bug fix or feature implementation. Ensure your code is well-commented.
3. **Commit and Push Changes:** Make your changes, including any necessary tests, and commit them to your branch. Then, push your changes to your forked repository.
4. **Submit a Pull Request:** Once your changes are ready, submit a pull request to merge them into the main project's `master` branch.
5. **Test Your Code:** Before submitting your pull request, ensure that your code works properly by testing it in the [laravel-chunk-upload-example](https://github.com/pionl/laravel-chunk-upload-example) project.
6. **Debugging Assistance:** If you encounter any issues, consider using XDEBUG for debugging purposes.

## Pull Requests

When submitting pull requests, please adhere to the following guidelines:

- **Coding Standards:** Follow the [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md). You can easily apply these conventions using `composer run lint:fix`.

- **Release Cycle:** Consider our release cycle, aiming to follow [SemVer v2.0.0](http://semver.org/).

- **Document Changes:** Document any changes in behavior thoroughly, ensuring that the `README.md` and other relevant documentation are updated accordingly.

- **Feature Branches:** Create feature branches for your pull requests rather than requesting to pull from your master branch directly.

- **Single Feature per Pull Request:** Submit one pull request per feature. If you're implementing multiple features, send separate pull requests for each.

Before submitting your pull request:

1. **Rebase Changes:** Rebase your changes on the master branch to ensure a clean commit history.
2. **Lint Project:** Check for any coding standard violations using `composer run lint`.
3. **Run Tests:** Ensure that all tests pass by running `composer run test`.
4. **Write Tests (Recommended):** If possible, write tests to cover your code changes.
5. **Rebase Commits (Optional):** Consider rebasing your commits to keep them concise and relevant.

Thank you for your contributions!

# Adding new library

The `AbstractHandler` class provides a foundation for implementing custom detection of chunk mode and file naming. While it's stored in the Handler namespace by default, you can place your handler in any namespace and pass the class to the `FileReceiver` as a parameter.

### You Must Implement:

- `getChunkFileName()`: Returns the chunk file name for storing the temporary file.
- `isFirstChunk()`: Checks if the request contains the first chunk.
- `isLastChunk()`: Checks if the current request contains the last chunk.
- `isChunkedUpload()`: Checks if the current request is a chunked upload.
- `getPercentageDone()`: Calculates the current upload percentage.

### Automatic Detection

To enable your own detection, simply override the `canBeUsedForRequest` method:

```php
public static function canBeUsedForRequest(Request $request)
{
    return true;
}
```

# Your additions to your code base

If you wish to contribute without forking, follow these steps:

1. **Edit HandlerFactory:** Add your handler to the `$handlers` array.

2. **Runtime Usage:** Call `HandlerFactory::register($name)` at runtime to register your custom Handler and utilize it.

Feel free to contribute and thank you for your support!
