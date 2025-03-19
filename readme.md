# Laravel Chunk Upload

[![Total Downloads](https://poser.pugx.org/pion/laravel-chunk-upload/downloads?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![Build Status](https://github.com/pionl/laravel-chunk-upload/workflows/build/badge.svg)](https://github.com/pionl/laravel-chunk-upload/actions)
[![Latest Stable Version](https://poser.pugx.org/pion/laravel-chunk-upload/v/stable?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![License](https://poser.pugx.org/pion/laravel-chunk-upload/license)](https://packagist.org/packages/pion/laravel-chunk-upload)

## Introduction

Laravel Chunk Upload simplifies chunked uploads with support for multiple JavaScript libraries atop Laravel's file upload system, designed with a minimal memory footprint. Features include cross-domain request support, automatic cleaning, and intuitive usage.

For example repository with **integration tests**, visit [laravel-chunk-upload-example](https://github.com/pionl/laravel-chunk-upload-example).

Before contributing, familiarize yourself with the guidelines outlined in CONTRIBUTION.md.

## Installation

**1. Install via Composer**

```bash
composer require pion/laravel-chunk-upload
```

**2. Publish the Configuration (Optional)**

```bash
php artisan vendor:publish --provider="Pion\Laravel\ChunkUpload\Providers\ChunkUploadServiceProvider"
```

## Usage

The setup involves three steps:

1. Integrate your controller to handle file uploads. [Instructions](https://github.com/pionl/laravel-chunk-upload/wiki/controller)
2. Define a route for the controller. [Instructions](https://github.com/pionl/laravel-chunk-upload/wiki/routing)
3. Select your preferred frontend provider (multiple providers are supported in a single controller).

| Library | Wiki | Single & Chunk Upload | Simultaneous Uploads | Included in [Example Project](https://github.com/pionl/laravel-chunk-upload-example) | Author |
|---------|------|-----------------------|----------------------|--------------------------------------------------|--------|
| [resumable.js](https://github.com/23/resumable.js) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/resumable-js) | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | [@pionl](https://github.com/pionl) |
| [DropZone](https://github.com/dropzone/dropzone) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/dropzone) | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | [@pionl](https://github.com/pionl) |
| [jQuery-File-Upload](https://github.com/blueimp/jQuery-File-Upload) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/jquery-file-upload)  | :heavy_check_mark: | :heavy_multiplication_x: | :heavy_check_mark: | [@pionl](https://github.com/pionl) |
| [Plupload](https://github.com/moxiecode/plupload) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/plupload) | :heavy_check_mark: | :heavy_multiplication_x: | :heavy_multiplication_x: | [@pionl](https://github.com/pionl) |
| [simple uploader](https://github.com/simple-uploader) | :heavy_multiplication_x: | :heavy_check_mark: | :heavy_multiplication_x: | :heavy_multiplication_x: | [@dyktek](https://github.com/dyktek) |
| [ng-file-upload](https://github.com/danialfarid/ng-file-upload) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/ng-file-upload) | :heavy_check_mark: | :heavy_multiplication_x: | :heavy_multiplication_x: | [@L3o-pold](https://github.com/L3o-pold) |

**Simultaneous Uploads:** The library must send the last chunk as the final one to ensure correct merging.

**Custom Disk:** Currently, it's recommended to use the basic storage setup (not linking the public folder). If you have time to verify its functionality, please PR the changes!

For detailed information and tips, refer to the [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki) or explore a working example in a separate repository with [example](https://github.com/pionl/laravel-chunk-upload-example).

## Changelog

View the changelog in [releases](https://github.com/pionl/laravel-chunk-upload/releases).

## Contribution or Extension

Review the contribution guidelines before submitting your PRs (and utilize the example repository for running integration tests).

Refer to [CONTRIBUTING.md](CONTRIBUTING.md) for contribution instructions. All contributions are welcome.

## Compatibility

Though not tested via automation scripts, Laravel 5/6 should still be supported.

| Version | PHP           |
|---------|---------------| 
| 12.*    | 8.2,8.3,8.4   |
| 11.*    | 8.2,8.3,8.4   |
| 10.*    | 8.1, 8.2      |
| 9.*     | 8.0, 8.1      |
| 8.*     | 7.4, 8.0, 8.1 |
| 7.*     | 7.4           |

### New versions

If there is a new Laravel version and there is not a offical release you can create a PR and then any one can use the PR until offical release is made. Check the PR and and update composer.json with the repository.

```
"repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/{!ENTER_USER_NAME!}/laravel-chunk-upload"
    }
  ]
```

Here is [exmplanation article](https://putyourlightson.com/articles/requiring-a-forked-repo-with-composer)

## Copyright and License

[laravel-chunk-upload](https://github.com/pionl/laravel-chunk-upload) was authored by [Martin Kluska](http://kluska.cz) and is released under the [MIT License](LICENSE.md).

Copyright (c) 2017 and beyond Martin Kluska and all contributors (Thank you ❤️)
