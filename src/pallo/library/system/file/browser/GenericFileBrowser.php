<?php

namespace pallo\library\system\file\browser;

use pallo\library\system\exception\FileSystemException;
use pallo\library\system\file\File;

/**
 * Generic file browser to find files in the file system structure
 */
class GenericFileBrowser implements FileBrowser {

    /**
     * Application directory
     * @var pallo\library\system\file\File
     */
    protected $applicationDirectory;

    /**
     * Public directory
     * @var pallo\library\system\file\File
     */
    protected $publicDirectory;

    /**
     * Public path in the include directories
     * @var string
     */
    protected $publicPath;

    /**
     * Array containing the directories of the file system structure
     * @var array
     */
    protected $includeDirectories = array();

    /**
     * Adds a include directory.
     * @param pallo\library\system\file\File $directory
     * @return null
     */
    public function addIncludeDirectory(File $directory) {
        $this->includeDirectories[$directory->getAbsolutePath()] = $directory;
    }

    /**
     * Removes a include directory
     * @param pallo\library\system\file\File $directory
     * @return null
     */
    public function removeIncludeDirectory($directory) {
        if ($directory instanceof File) {
            $directory = $directory->getAbsolutePath();
        }

        if (isset($this->includeDirectories[$directory])) {
            unset($this->includeDirectories[$directory]);
        }
    }

    /**
     * Gets the first file in the include paths
     * @param string $file Relative path of a file in the include paths
     * @return pallo\library\system\file\File|null Instance of the file if found,
     * null otherwise
     */
    public function getIncludeDirectories() {
        return $this->includeDirectories;
    }

    /**
     * Sets the application directory
     * @param string|pallo\library\system\file\File $directory
     * @return null
     */
    public function setApplicationDirectory(File $directory) {
        $this->applicationDirectory = $directory;

        // make sure the application directory is the first include directory
        $path = $directory->getAbsolutePath();

        $this->removeIncludeDirectory($path);

        $this->includeDirectories = array($path => $directory) + $this->includeDirectories;
    }

    /**
     * Gets the application directory
     * @return pallo\library\system\file\File
     */
    public function getApplicationDirectory() {
        return $this->applicationDirectory;
    }

    /**
     * Sets the public directory
     * @param string|pallo\library\system\file\File $directory
     * @return null
     */
    public function setPublicDirectory(File $directory) {
        $this->publicDirectory = $directory;
    }

    /**
     * Gets the public directory
     * @return pallo\library\system\file\File
     */
    public function getPublicDirectory() {
        return $this->publicDirectory;
    }

    /**
     * Sets the public path of the include directories
     * @param string $path
     * @return null
     */
    public function setPublicPath($path) {
        $this->publicPath = $path;
    }

    /**
     * Gets the public path of the include directories
     * @return string
     */
    public function getPublicPath() {
        return $this->publicPath;
    }

    /**
     * Gets the first file in the public domain
     * @param string $file Relative path of a file in the public domain
     * @return pallo\library\system\file\File|null Instance of the file if found,
     * null otherwise
     */
    public function getPublicFile($file) {
        $fileName = ltrim($file, '/');

        if ($this->publicDirectory) {
            $file = $this->publicDirectory->getChild($fileName);
            if ($file->exists()) {
                return $file;
            }
        }

        if ($this->publicPath) {
            return $this->getFile($this->publicPath . File::DIRECTORY_SEPARATOR . $fileName);
        }

        return null;
    }

    /**
     * Gets the first file in the include paths
     * @param string $file Relative path of a file in the include paths
     * @return pallo\library\system\file\File|null Instance of the file if found,
     * null otherwise
     */
    public function getFile($file) {
        return $this->lookupFile($file, true);
    }

    /**
     * Gets all the files in the include paths
     * @param string $file Relative path of a file in the include paths
     * @return array array with File instances
     * @see pallo\library\system\file\File
     */
    public function getFiles($file) {
        return $this->lookupFile($file, false);
    }

    /**
     * Look for files by looping through the include paths
     * @param string $fileName Relative path of a file in the file system
     * structure
     * @param boolean $firstOnly True to get the first matched file, false to
     * get an array with all the matched files
     * @return pallo\library\system\file\File|array Depending on the firstOnly
     * flag, an instance of pallo\library\system\file\File or an array
     * @throws pallo\library\system\exception\FileSystemException when the
     * provided file name is empty or not a string
     */
    protected function lookupFile($fileName, $firstOnly) {
        if (!($fileName instanceof File) && (!is_string($fileName) || $fileName == '')) {
            throw new FileSystemException('Could not lookup file: Provided file name is empty or invalid');
        }

        $files = array();

        foreach ($this->includeDirectories as $includeDirectory) {
            $file = $includeDirectory->getChild($fileName);

            if (!$file->exists()) {
                continue;
            }

            if ($firstOnly) {
                return $file;
            }


            $files[$file->getPath()] = $file;
        }

        if ($firstOnly) {
            return null;
        }

        return $files;
    }

    /**
     * Gets the relative file in the include paths for a given absolute file
     * @param string|pallo\library\system\file\File $file Path to a file to get
     * the relative file from
     * @param boolean $public Set to true to check the public directory as well
     * @return pallo\library\system\file\File relative file in the file system
     * structure if located in one of the include paths
     * @throws pallo\library\system\exception\FileSystemException when the
     * provided file is not in one of the include paths
     */
    public function getRelativeFile($file, $public = false) {
        $fileSystem = null;
        $absoluteFile = null;

        foreach ($this->includeDirectories as $includeDirectory) {
            if ($absoluteFile === null) {
                $fileSystem = $includeDirectory->getFileSystem();

                $file = $fileSystem->getFile($file);

                $absoluteFile = $file->getAbsolutePath();

                $isPhar = $file->hasPharProtocol();
                if ($isPhar) {
                    $absoluteFile = substr($absoluteFile, 7);
                }
            }

            $includeAbsolutePath = $includeDirectory->getAbsolutePath();
            if (strpos($absoluteFile, $includeAbsolutePath) !== 0) {
                continue;
            }

            $relativeFile = str_replace($includeAbsolutePath . File::DIRECTORY_SEPARATOR, '', $absoluteFile);

            return $fileSystem->getFile($relativeFile);
        }

        if ($public) {
            $publicAbsolutePath = $this->publicDirectory->getAbsolutePath();

            if (strpos($absoluteFile, $publicAbsolutePath) === 0) {
                $relativeFile = str_replace($publicAbsolutePath . File::DIRECTORY_SEPARATOR, '', $absoluteFile);

                return $fileSystem->getFile($relativeFile);
            }
        }

        throw new FileSystemException($file . ' is not in the file system structure');
    }

}