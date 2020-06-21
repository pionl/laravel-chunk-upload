# Laravel Chunk Upload

[![Total Downloads](https://poser.pugx.org/pion/laravel-chunk-upload/downloads?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![Build Status](https://travis-ci.org/pionl/laravel-chunk-upload.svg?branch=master)](https://travis-ci.org/pionl/laravel-chunk-upload)
[![Latest Stable Version](https://poser.pugx.org/pion/laravel-chunk-upload/v/stable?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![Latest Unstable Version](https://poser.pugx.org/pion/laravel-chunk-upload/v/unstable?format=flat)](https://packagist.org/packages/pion/laravel-chunk-upload)
[![License](https://poser.pugx.org/pion/laravel-chunk-upload/license)](https://packagist.org/packages/pion/laravel-chunk-upload)

## Introduction

> Supports Laravel from 5.2 to 7.

Easy to use service/library for chunked upload with supporting multiple JS libraries on top of Laravel's file upload with low memory footprint in mind. 

Supports feature as [cross domains requests](https://github.com/pionl/laravel-chunk-upload/wiki/cross-domain-requests), automatic clean schedule and easy usage.

Example repository with integration test can be found in [laravel-chunk-upload-example](https://github.com/pionl/laravel-chunk-upload-example).

> Before adding pull requests read CONTRIBUTION.md

## Installation

**1. Install via composer**

```
composer require pion/laravel-chunk-upload
```

**2. Publish the config (Optional)**

```
php artisan vendor:publish --provider="Pion\Laravel\ChunkUpload\Providers\ChunkUploadServiceProvider"
```

## Usage

Setup consists of 3 steps:

1. Integrate your controller that will handle the file upload. [How to](https://github.com/pionl/laravel-chunk-upload/wiki/controller)
2. Set a route for the controller. [How to](https://github.com/pionl/laravel-chunk-upload/wiki/routing)
2. Choose your front-end provider below (we support multiple providers in single controller) 

| Library | Wiki | single & chunk upload | simultaneous uploads | In [example project](https://github.com/pionl/laravel-chunk-upload-example) | Author |
|---- |----|----|----| ---- | ---- |
| [resumable.js](https://github.com/23/resumable.js) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/resumable-js) | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | [@pionl](https://github.com/pionl) |
| [DropZone](https://gitlab.com/meno/dropzone/) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/dropzone) | :heavy_check_mark: | :heavy_check_mark: | :heavy_check_mark: | [@pionl](https://github.com/pionl) |
| [jQuery-File-Upload](https://github.com/blueimp/jQuery-File-Upload) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/jquery-file-upload)  | :heavy_check_mark: | :heavy_multiplication_x: | :heavy_check_mark: | [@pionl](https://github.com/pionl) |
| [Plupload](https://github.com/moxiecode/plupload) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/plupload) | :heavy_check_mark: | :heavy_multiplication_x: | :heavy_multiplication_x: | [@pionl](https://github.com/pionl) |
| [simple uploader](https://github.com/simple-uploader) | :heavy_multiplication_x: | :heavy_check_mark: | :heavy_multiplication_x: | :heavy_multiplication_x: | [@dyktek](https://github.com/dyktek) |
| [ng-file-upload](https://github.com/danialfarid/ng-file-upload) | [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki/ng-file-upload) | :heavy_check_mark: | :heavy_multiplication_x: | :heavy_multiplication_x: | [@L3o-pold](https://github.com/L3o-pold) |

**Simultaneous uploads:** The library must send last chunk as last, otherwise the merging will not work correctly.

**Custom disk:** At this moment I recommend using the basic storage setup (not linking public folder). It is not tested (Have free time to ensure it is working? PR the changes!).

For more detailed information (tips) use the [Wiki](https://github.com/pionl/laravel-chunk-upload/wiki) or for working example continue to separate repository with [example](https://github.com/pionl/laravel-chunk-upload-example).

## Changelog

Can be found in [releases](https://github.com/pionl/laravel-chunk-upload/releases).

## Contribution or extending

> Read contribution before your PR (and use example repository to run integration tests).

See [CONTRIBUTING.md](CONTRIBUTING.md) for how to contribute changes. All contributions are welcome.

## Compatibility

* 7.* (last tested on v7.16.1)
* 6.* (last tested on v6.18.20)
* 5.8.* (last tested on v5.8.38)
* 5.7.* (last tested on v5.7.29)
* 5.6.* (last tested on v5.6.40)
* 5.5.* (last tested on v5.5.49)
* 5.4.* (last tested on v5.4.36)
* 5.3.* (last tested on v5.3.31)
* 5.2.* (last tested on v5.2.45)


## Copyright and License

[laravel-chunk-upload](https://github.com/pionl/laravel-chunk-upload)
was written by [Martin Kluska](http://kluska.cz) and is released under the 
[MIT License](LICENSE.md).

Copyright (c) 2016 and beyond Martin Kluska
