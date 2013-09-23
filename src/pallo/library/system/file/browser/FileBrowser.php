<?php

namespace pallo\library\system\file\browser;

/**
 * Interface to find files in the file system structure
 */
interface FileBrowser {

    /**
     * Gets the public directory
     * @return pallo\library\system\file\File
     */
    public function getPublicDirectory();

    /**
     * Gets the first file in the public directory structure
     * @param string $file Relative path
     * @return pallo\library\system\file\File|null Instance of the file if found,
     * null otherwise
     */
    public function getPublicFile($file);

    /**
     * Gets the base directories of the Zibo filesystem structure. This will
     * return the directory of application first, then the directories of the
     * actual modules
     * @return array Array with File instances
     */
    public function getIncludeDirectories();

    /**
     * Gets the first file in the Zibo filesystem structure according to the
     * provided path.
     * @param string $file Relative path of a file in the Zibo filesystem
     * structure
     * @return pallo\library\system\file\File|null Instance of the file if found,
     * null otherwise
     */
    public function getFile($file);

    /**
     * Gets all the files in the Zibo filesystem structure according to the
     * provided path.
     * @param string $file Relative path of a file in the Zibo filesystem
     * structure
     * @return array array with File instances
     * @see pallo\library\system\file\File
     */
    public function getFiles($file);

    /**
     * Gets the relative file in the file system structure for a given
     * absolute file.
     * @param string|pallo\library\system\file\File $file Path to a file to get
     * the relative file from
     * @param boolean $public Set to true to check the public directory as well
     * @return pallo\library\system\file\File relative file in the file system
     * structure if located in one of the include paths
     * @throws pallo\library\system\exception\FileSystemException when the
     * provided file is not in one of the include paths
     */
    public function getRelativeFile($file, $public);

}