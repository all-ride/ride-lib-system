<?php

namespace pallo\library\system\file\browser;

use pallo\library\system\exception\FileSystemException;
use pallo\library\system\file\File;

/**
 * Generic file browser to find files in the file system structure
 */
class GenericFileBrowser implements FileBrowser {

    /**
     * Public directory
     * @var pallo\library\system\file\File
     */
    protected $publicDirectory;

    /**
     * Array containing the directories of the file system structure
     * @var array
     */
    protected $includeDirectories = array();

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
     * Gets the first file in the public directory structure
     * @param string $file Relative path
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

    	return $this->getFile('public/' . $fileName);
    }

    /**
     * Adds a include directory.
     * @param pallo\library\system\file\File $directory
     * @return null
     */
    public function addIncludeDirectory(File $directory) {
        $this->includeDirectories[$directory->getPath()] = $directory;
    }

    /**
     * Removes a include directory
     * @param pallo\library\system\file\File $directory
     * @return null
     */
    public function removeIncludeDirectory($directory) {
    	if ($directory instanceof File) {
        	$directory = $directory->getPath();
    	}

        if (isset($this->includeDirectories[$directory])) {
            unset($this->includeDirectories[$directory]);
        }
    }

    /**
     * Gets all the include directories
     * @return array Array with File instances
     */
    public function getIncludeDirectories() {
        return $this->includeDirectories;
    }

    /**
     * Gets the first file in the file system structure according to the
     * provided path.
     * @param string $file Relative path of a file in the file system
     * structure
     * @return pallo\library\system\file\File|null Instance of the file if found,
     * null otherwise
     */
    public function getFile($file) {
        return $this->lookupFile($file, true);
    }

    /**
     * Gets all the files in the file system structure according to the
     * provided path.
     * @param string $file Relative path of a file in the file system structure
     * @return array Array with File instances
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
     * Gets the relative file in the file system structure for a given
     * absolute file.
     * @param string|pallo\library\system\file\File $file Path to a file to get
     * the relative file from
     * @param boolean $public Set to true to check the public directory as well
     * @return pallo\library\system\file\File relative file in the file system
     * structure
     * @throws pallo\library\system\exception\FileSystemException when the
     * provided file is not part of the file system structure
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