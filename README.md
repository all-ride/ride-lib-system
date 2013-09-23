# Pallo: System Library

System library of the PHP Pallo framework.

## Code Sample

Check this code sample to see the possibilities of this library:

    <?php
    
    use pallo\library\system\System;

    $system = new System();
    
    // check the type of operating system
    $system->isUnix();
    $system->isWindows();
    
    // execute a command
    $output = $system->execute('whoami');
    
    // file system abstraction
    $fileSystem = $system->getFileSystem();
    
    $dir = $fileSystem->getFile('path/to/dir');
    $dir->isDirectory();
    
    $file = $fileSystem->getFile('path/to/file');
    $file->exists();
    
    $destination = $dir->getChild($file->getName();
    $destination = $destination->getCopyFile(); 
    
    $file->copy($destination);