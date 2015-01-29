<?php

namespace ride\library\system\file;

/**
 * Abstract file system implementation
 */
interface FileSystem {

    /**
     * Gets a instance of a file
     * @param string $path
     * @return File
     */
    public function getFile($path);

    /**
     * Get the absolute path for a file
     * @param File $file
     * @return string
     */
    public function getAbsolutePath(File $file);

    /**
     * Check whether a file has an absolute path
     * @param File $file
     * @return boolean True when the file has an absolute path
     */
    public function isAbsolute(File $file);

    /**
     * Check whether a path is a root path (/, c:/, //server)
     * @param string $path
     * @return boolean True when the file is a root path, false otherwise
     */
    public function isRootPath($path);

    /**
     * Get the parent of the provided file
     *
     * If you provide a path like /var/www/site, the parent will be /var/www
     * @param File $file
     * @return File Parent of the file
     */
    public function getParent(File $file);

    /**
     * Checks whether a file exists
     * @param File $file
     * @return boolean True when the file exists, false otherwise
     */
    public function exists(File $file);
    /**
     * Checks whether a file is a directory
     * @param File $file
     * @return boolean True when the file is a directory, false otherwise
     */
    public function isDirectory(File $file);

    /**
     * Checks whether a file is readable
     * @param File $file
     * @return boolean True when the file is readable, false otherwise
     */
    public function isReadable(File $file);

    /**
     * Checks whether a file is writable.
     *
     * When the file exists, the file itself will be checked. If not, the
     * parent directory will be checked
     * @param File $file
     * @return boolean true when the file is writable, false otherwise
     */
    public function isWritable(File $file);

    /**
     * Checks whether a file is hidden.
     * @param File $file
     * @return boolean true when the file is hidden, false otherwise
     */
    public function isHidden(File $file);

    /**
     * Get the timestamp of the last write to the file
     * @param File $file
     * @return int timestamp of the last modification
     * @throws \ride\library\system\exception\FileSystemException when the
     * file does not exist
     * @throws \ride\library\system\exception\FileSystemException when the
     * modification time could not be read
     */
    public function getModificationTime(File $file);

    /**
     * Get the size of a file
     * @param File $file
     * @return int size of the file in bytes
     * @throws \ride\library\system\exception\FileSystemException when the
     * file is a directory
     * @throws \ride\library\system\exception\FileSystemException when the
     * file size could not be read
     */
    public function getSize(File $file);

    /**
     * Get the permissions of a file or directory
     * @param File $file
     * @return int an octal value of the permissions. eg. 0755
     * @throws \ride\library\system\exception\FileSystemException when the
     * file or directory does not exist
     * @throws \ride\library\system\exception\FileSystemException when the
     * permissions could not be read
     */
    public function getPermissions(File $file);

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
    public function setPermissions(File $file, $permissions);

    /**
     * Read a file or directory
     * @param File $file file or directory to read
     * @return string|array when reading a file, a string with the content of
     * the file will be returned. When reading a directory, an array will be
     * returned containing File objects as value and the paths as key.
     * @throws \ride\library\system\exception\FileSystemException when the
     * file or directory could not be read
     */
    public function read(File $file, $recursive = false);

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
    public function write(File $file, $content = '', $append = false);

    /**
     * Sets the modification time of the provided file
     * @param File $fiole File to touch
     * @param integer $time Timestamp of the modification time
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * file could not be touched
     */
    public function touch(File $file, $time = null);

    /**
     * Create a directory
     * @param File $dir
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * directory could not be created
     */
    public function create(File $dir);

    /**
     * Delete a file or directory
     * @param File $file
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * file or directory could not be deleted
     */
    public function delete(File $file);

    /**
     * Copy a file or directory to another destination
     * @param File $source
     * @param File $destination
     * @return null
     * @throws \ride\library\system\exception\FileSystemException when the
     * source could not be copied
     */
    public function copy(File $source, File $destination);

    /**
     * Move a file to another directory
     * @param File $source source file for the move
     * @param File $destination new destination for the source file
     * @return null
     */
    public function move(File $source, File $destination);

}
