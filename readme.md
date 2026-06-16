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

### Laravel 13 — use this fork (recommended)

Upstream [Packagist](https://packagist.org/packages/pion/laravel-chunk-upload) does not ship Laravel 13 support yet. Point Composer at this fork and install the **tagged release** from `master`:

- Fork: [andydev271/laravel-chunk-upload](https://github.com/andydev271/laravel-chunk-upload)
- Latest release: [`1.5.9`](https://github.com/andydev271/laravel-chunk-upload/releases/tag/1.5.9)
- Default branch: `master` (includes Laravel 13 support)

Add to your Laravel app's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/andydev271/laravel-chunk-upload"
    }
],
"require": {
    "pion/laravel-chunk-upload": "1.5.9"
}
```

Or from the command line:

```bash
composer config repositories.laravel-chunk-upload vcs https://github.com/andydev271/laravel-chunk-upload
composer require pion/laravel-chunk-upload:1.5.9
```

No `minimum-stability: dev` is needed when using a tagged release.

### Laravel 13 — development branch (optional)

Keep `laravel-13-support` only if you need commits **after** the latest tag, before the next release is published. Most users should use the tagged release above.

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/andydev271/laravel-chunk-upload"
    }
],
"require": {
    "pion/laravel-chunk-upload": "dev-laravel-13-support as 1.5.9"
},
"minimum-stability": "dev",
"prefer-stable": true
```

```bash
composer config repositories.laravel-chunk-upload vcs https://github.com/andydev271/laravel-chunk-upload
composer require pion/laravel-chunk-upload:dev-laravel-13-support
```

### Laravel 12 and below

Install from Packagist (no fork needed):

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

View upstream changelog in [releases](https://github.com/pionl/laravel-chunk-upload/releases).

Fork releases for Laravel 13: [andydev271/laravel-chunk-upload releases](https://github.com/andydev271/laravel-chunk-upload/releases).

## Creating a release (maintainers)

**Branch strategy**

| Branch | Purpose |
|--------|---------|
| `master` | Default branch. Users install tagged releases from here. |
| `laravel-13-support` | Optional. Use for new Laravel 13 work before the next tag; merge into `master` when ready to release. |

**Publish a new release** (e.g. `1.6.0` after fixes on `laravel-13-support`):

```bash
# 1. Merge development branch into master
git checkout master
git pull origin master
git merge laravel-13-support
git push origin master

# 2. Tag the new version
git tag -a 1.6.0 -m "Laravel 13 support"
git push origin 1.6.0

# 3. Create or update the GitHub release
gh auth login   # first time only
gh release create 1.6.0 \
  --repo andydev271/laravel-chunk-upload \
  --title "1.6.0 — Laravel 13 support" \
  --notes "Adds Laravel 13 compatibility. Install: composer require pion/laravel-chunk-upload:1.6.0"
```

**Update release notes only** (tag already exists):

```bash
gh release edit 1.5.9 \
  --repo andydev271/laravel-chunk-upload \
  --title "1.5.9 — Laravel 13 support" \
  --notes "Adds Laravel 13 compatibility.

Install in your Laravel app:
composer config repositories.laravel-chunk-upload vcs https://github.com/andydev271/laravel-chunk-upload
composer require pion/laravel-chunk-upload:1.5.9"
```

After publishing a tag, update the version in the [Installation](#installation) section of `readme.md` on both `master` and `laravel-13-support`.

Do not move or delete published tags; publish a new patch version instead (e.g. `1.6.0`).

## Contribution or Extension

Review the contribution guidelines before submitting your PRs (and utilize the example repository for running integration tests).

Refer to [CONTRIBUTING.md](CONTRIBUTING.md) for contribution instructions. All contributions are welcome.

## Compatibility

Though not tested via automation scripts, Laravel 5/6 should still be supported.

| Version | PHP           |
|---------|---------------| 
| 13.*    | 8.2, 8.3, 8.4 |
| 12.*    | 8.2,8.3,8.4   |
| 11.*    | 8.2,8.3,8.4   |
| 10.*    | 8.1, 8.2      |
| 9.*     | 8.0, 8.1      |
| 8.*     | 7.4, 8.0, 8.1 |
| 7.*     | 7.4           |

### Laravel 13 (fork install)

Laravel 13 is not yet on [Packagist](https://packagist.org/packages/pion/laravel-chunk-upload). Use this fork:

| What | Value |
|------|-------|
| Repository | [andydev271/laravel-chunk-upload](https://github.com/andydev271/laravel-chunk-upload) |
| Install (recommended) | Tagged release `1.5.9` from `master` |
| Install (bleeding edge) | Branch `laravel-13-support` via `dev-laravel-13-support` |

See [Installation](#installation) for Composer commands. See [Creating a release (maintainers)](#creating-a-release-maintainers) to publish updates.

## Copyright and License

[laravel-chunk-upload](https://github.com/pionl/laravel-chunk-upload) was authored by [Martin Kluska](http://kluska.cz) and is released under the [MIT License](LICENSE.md).

Copyright (c) 2017 and beyond Martin Kluska and all contributors (Thank you ❤️)
