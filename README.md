# Ride: System Library

System abstraction library of the PHP Ride framework.

## What's In This Library

### System

The _System_ class in an abstraction of the underlying server system.
It offers easy access to the underlying file system.
You can also use it to execute commands or check the type of system, the connected client, ...

### FileSystem

The _FileSystem_ interface is an abstraction of the underlying file system.
A Windows file system is handled different then a Unix file system.
This interface makes it possible to program transparantly for both systems.

### File

The file system works with _File_ objects.
All file operations are to be called through this class.

### FileBrowser

The _FileBrowser_ interface is to create a transparant file structure.
You can use it to request relative files without knowing how your files are organized.
On top of that, you can request the application and the public directory.

### PermissionConverter

You can use the _PermissionConverter_ to convert permissions to different formats.

## Code Sample

Check this code sample to see the possibilities of this library:

```php
<?php

use ride\library\system\System;

$system = new System();

// check the type of operating system
$system->isUnix();
$system->isWindows();

// check the client
$system->isCli();
$system->getClient(); // ip address or username when in cli

// execute a command
$output = $system->execute('whoami');

// file system abstraction
$fileSystem = $system->getFileSystem();

$dir = $fileSystem->getFile('/path/to/dir');
$dir->isDirectory();
$dir->isReadable();
$files = $dir->read();

$file = $fileSystem->getFile('/path/to/file');
$file->exists();
$file->getModificationTime();
$file->setLock(true);
$content = $file->read();

$destination = $dir->getChild($file->getName());
$destination = $destination->getCopyFile();

$file->copy($destination);
```

### Implementations

For more examples, you can check the following implementation of this library:
- [ride/app](https://github.com/all-ride/ride-app)

## Installation

You can use [Composer](http://getcomposer.org) to install this library.

```
composer require ride/lib-system
```
