<?php

namespace ride\library\system\file;

use ride\library\system\exception\FileSystemException;

/**
 * Abstract file system implementation
 */
abstract class AbstractFileSystem implements FileSystem {

    /**
     * Gets a instance of a file
     * @param string $path
     * @return File
     */
    public function getFile($path) {
        return new File($this, $path);
    }

    /**
     * Creates a temporary file
     * @param string $name Prefix for the name
     * @return \ride\library\system\file\File
     */
    public function getTemporaryFile($name = 'temp') {
        return $this->getFile(tempnam(sys_get_temp_dir(), $name));
    }

    /**
     * Checks whether a file exists
     * @param File $file
     * @return boolean True when the file exists, false otherwise
     */
    public function exists(File $file) {
        $path = $this->getAbsolutePath($file);
        clearstatcache();

        return @file_exists($path);
    }

    /**
     * Checks whether a file is a directory
     * @param File $file
     * @return boolean True when the file is a directory, false otherwise
     */
    public function isDirectory(File $file) {
        if (!$this->exists($file)) {
            return false;
        }

        $path = $this->getAbsolutePath($file);

        return @is_dir($path);
    }

    /**
     * Checks whether a file is readable
     * @param File $file
     * @return boolean True when the file is readable, false otherwise
     */
    public function isReadable(File $file) {
        if (!$this->exists($file)) {
            return false;
        }

        $path = $this->getAbsolutePath($file);

        return @is_readable($path);
    }

    /**
     * Checks whether a file is writable.
     *
     * When the file exists, the file itself will be checked. If not, the
     * parent directory will be checked
     * @param File $file
     * @return boolean true when the file is writable, false otherwise
     */
    public function isWritable(File $file) {
        if ($this->exists($file)) {
            $path = $this->getAbsolutePath($file);

            return @is_writable($path);
        } else {
            return $this->isWritable($this->getParent($file));
        }
    }

    /**
     * Get the timestamp of the last write to the file
     * @param File $file
     * @return int timestamp of the last modification
     * @throws \ride\library\system\exception\FileSystemException when the
     * file does not exist
     * @throws \ride\library\system\exception\FileSystemException when the
     * modification time could not be read
     */
    public function getModificationTime(File $file) {
        if (!$this->exists($file)) {
            throw new FileSystemException('Could not get the modification time of ' . $file->getAbsolutePath() . ': file does not exist');
        }

        $path = $this->getAbsolutePath($file);

        $time = @filemtime($path);
        if ($time === false) {
            $error = error_get_last();

            throw new FileSystemException('Cannot get the modification time of ' . $path . ': ' . $error['message']);
        }

        return $time;
    }

    /**
     * Get the size of a file
     * @param File $file
     * @return int size of the file in bytes
     * @throws \ride\library\system\exception\FileSystemException when the
     * file is a directory
     * @throws \ride\library\system\exception\FileSystemException when the
     * file size could not be read
     */
    public function getSize(File $file) {
        if ($this->isDirectory($file)) {
            throw new FileSystemException('Could not get the size of ' . $file->getAbsolutePath() . ': file is a directory');
        }

        $path = $this->getAbsolutePath($file);

        $size = @filesize($path);
        if ($size === false) {
            $error = error_get_last();

            throw new FileSystemException('Could not get the size of ' . $path . ': ' . $error['message']);
        }

        return $size;
    }

    /**
     * Get the permissions of a file or directory
     * @param File $file
     * @return int an octal value of the permissions. eg. 0755
     * @throws \ride\library\system\exception\FileSystemException when the
     * file or directory does not exist
     * @throws \ride\library\system\exception\FileSystemException when the
     * permissions could not be read
     */
    public function getPermissions(File $file) {
        if (!$this->exists($file)) {
            throw new FileSystemException('Could not get the permissions of ' . $file->getAbsolutePath() . ': file does not exist');
        }

        $path = $this->getAbsolutePath($file);

        $mode = @fileperms($path);
        if ($mode === false) {
            $error = error_get_last();

            throw new FileSystemException('Could not get the permissions of ' . $path . ': ' . $error['message']);
        }

        // fileperms() returns other bits in addition to the permission bits,
        // like SUID, SGID and sticky bits. We only want the permission bits...
        $permissions = $mode & 0777;

        return $permissions;
    }

    /**
     * Set the permissions of a file or directory
     * @param File $file
     * @param int $permissions an octal value of the permissions, so strings
     * (such as "g+w") will not work properly. To ensure expected operation,
     * you need to prefix mode with a zero (0). eg. 0755
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * file or directory does not exist
     * @throws \ride\library\system\exception\FileSystemException when the
     * permissions could not be set
     */
    public function setPermissions(File $file, $permissions) {
        if (!$this->exists($file)) {
            throw new FileSystemException('Could not set the permissions of ' . $file->getAbsolutePath() . ' to ' . $permissions . ': file does not exist');
        }

        $path = $this->getAbsolutePath($file);

        $result = @chmod($path, $permissions);
        if ($result === false) {
            $error = error_get_last();

            throw new FileSystemException('Could not set the permissions of ' . $path . ' to ' . $permissions . ': ' . $error['message']);
        }
    }

    /**
     * Read a file or directory
     * @param File $file file or directory to read
     * @return string|array when reading a file, a string with the content of
     * the file will be returned. When reading a directory, an array will be
     * returned containing File objects as value and the paths as key.
     * @throws \ride\library\system\exception\FileSystemException when the
     * file or directory could not be read
     */
    public function read(File $file, $recursive = false) {
        if ($this->isDirectory($file) || $file->isPhar()) {
            return $this->readDirectory($file, $recursive);
        } else {
            return $this->readFile($file);
        }
    }

    /**
     * Read a file
     * @param File $file file to read
     * @return string the content of the file
     * @throws \ride\library\system\exception\FileSystemException when the
     * file could not be read
     */
    protected function readFile(File $file) {
        $path = $this->getAbsolutePath($file);

        $contents = @file_get_contents($path);
        if ($contents === false) {
            $error = error_get_last();

            throw new FileSystemException('Could not read ' . $path . ': ' . $error['message']);
        }

        return $contents;
    }

    /**
     * Read a directory
     * @param File $dir directory to read
     * @param boolean $recursive true to read the subdirectories, false
     * (default) to only read the given directory
     * @return array Array with a File object as value and it's path as key
     * @throws \ride\library\system\exception\FileSystemException when the
     * directory could not be read
     */
    protected function readDirectory(File $dir, $recursive = false) {
        $path = $this->getAbsolutePath($dir);

        if ($dir->isPhar() && !$dir->hasPharProtocol($path)) {
            $path = 'phar://' . $path;
        }

        if (!($handle = @opendir($path))) {
            $error = error_get_last();

            throw new FileSystemException('Could not read ' . $path . ': ' . $error['message']);
        }

        $ignore = array('.', '..');
        $files = array();

        while (($f = readdir($handle)) !== false) {
            if (in_array($f, $ignore)) {
                continue;
            }

            $file = $dir->getChild($f);
            $files[$file->getPath()] = $file;

            if ($recursive && $file->isDirectory()) {
                $tmp = $this->readDirectory($file, true);
                foreach ($tmp as $k => $v) {
                    $files[$k] = $v;
                }
            }
        }

        return $files;
    }

    /**
     * Write content to a file
     * @param File $file file to write
     * @param string $content new content for the file
     * @param boolean $append true to append to file, false (default) to
     * overwrite the file
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * file could not be written
     */
    public function write(File $file, $content = '', $append = false) {
        $path = $this->getAbsolutePath($file);

        $this->create($this->getParent($file));

        if ($append) {
            $stat = @file_put_contents($path, $content, FILE_APPEND);
        } else {
            $stat = @file_put_contents($path, $content);
        }

        if ($stat === false) {
            $error = error_get_last();

            throw new FileSystemException('Could not write ' . $path . ': ' . $error['message']);
        }
    }

    /**
     * Create a directory
     * @param File $dir
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * directory could not be created
     */
    public function create(File $dir) {
        if ($this->exists($dir)) {
            return;
        }

        $path = $this->getAbsolutePath($dir);

        $result = @mkdir($path, 0755, true);
        if ($result === false) {
            $error = error_get_last();

            throw new FileSystemException('Could not create ' . $path . ': ' . $error['message']);
        }
    }

    /**
     * Delete a file or directory
     * @param File $file
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * file or directory could not be deleted
     */
    public function delete(File $file) {
        if ($this->isDirectory($file)) {
            $this->deleteDirectory($file);
        } else {
            $this->deleteFile($file);
        }
    }

    /**
     * Delete a file
     * @param File $file
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * file could not be deleted
     */
    protected function deleteFile(File $file) {
        $path = $file->getAbsolutePath();

        $result = @unlink($path);
        if (!$result) {
            $error = error_get_last();

            throw new FileSystemException('Could not delete ' . $path . ': ' . $error['message']);
        }
    }

    /**
     * Delete a directory
     * @param File $dir
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * directory could not be read or deleted
     */
    protected function deleteDirectory(File $dir) {
        $path = $dir->getAbsolutePath();

        if (!($handle = @opendir($path))) {
            throw new FileSystemException('Could not delete ' . $path . ': directory could not be read');
        }

        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                $this->delete($dir->getChild($file));
            }
        }

        closedir($handle);

        $result = @rmdir($path);
        if (!$result) {
            $error = error_get_last();

            throw new FileSystemException('Could not delete ' . $path . ': ' . $error['message']);
        }
    }

    /**
     * Copy a file or directory to another destination
     * @param File $source
     * @param File $destination
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * source could not be copied
     */
    public function copy(File $source, File $destination) {
        if ($this->isDirectory($source)) {
            $this->copyDirectory($source, $destination);
        } else {
            $this->copyFile($source, $destination);
        }
    }

    /**
     * Copy a file to another file
     * @param File $source
     * @param File $destination
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * source could not be copied
     */
    protected function copyFile(File $source, File $destination) {
        $destinationParent = $destination->getParent();
        $this->create($destinationParent);

        $sourcePath = $source->getAbsolutePath();
        $destinationPath = $destination->getAbsolutePath();

        if ($sourcePath == $destinationPath) {
            return;
        }

        $result = @copy($sourcePath, $destinationPath);
        if (!$result) {
            $error = error_get_last();

            throw new FileSystemException('Could not copy ' . $sourcePath . ' to ' . $destinationPath . ': ' . $error['message']);
        }

        $this->setPermissions($destination, 0644); // $source->getPermissions());
    }

    /**
     * Copy a directory to another directory
     * @param File $source
     * @param File $destination
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * source could not be read
     */
    protected function copyDirectory(File $source, File $destination) {
        $sourcePath = $source->getAbsolutePath();
        if (!($handle = opendir($sourcePath))) {
            throw new FileSystemException('Could not read ' . $sourcePath);
        }

        $files = false;
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                $files = true;

                $this->copy($source->getChild($file), $destination->getChild($file));
            }
        }

        if (!$files) {
            // since copying an empty directory does nothing, we create the destination
            $this->create($destination);
        }
    }

    /**
     * Move a file to another directory
     * @param File $source source file for the move
     * @param File $destination new destination for the source file
     * @return null
     */
    public function move(File $source, File $destination) {
        if ($this->isDirectory($source)) {
            $this->copyDirectory($source, $destination);
            $this->delete($source);
        } else {
            $this->copyFile($source, $destination);
            $this->delete($source);
        }
    }

}
