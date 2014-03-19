# Ride: System Library

System abstraction library of the PHP Ride framework.

## Code Sample

Check this code sample to see the possibilities of this library:

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

    $dir = $fileSystem->getFile('path/to/dir');
    $dir->isDirectory();
    $dir->isReadable();

    $file = $fileSystem->getFile('path/to/file');
    $file->exists();
    $file->getModificationTime();

    $destination = $dir->getChild($file->getName());
    $destination = $destination->getCopyFile();

    $file->copy($destination);
