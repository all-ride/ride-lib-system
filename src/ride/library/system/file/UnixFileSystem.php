<?php

namespace ride\library\system\file;

/**
 * Filesystem implementation for Unix filesystems
 */
class UnixFileSystem extends AbstractFileSystem {

    /**
     * Check whether a path is a root path
     * @return boolean
     */
    public function isRootPath($path) {
        return strlen($path) == 1 && $path == File::DIRECTORY_SEPARATOR;
    }

    /**
     * Checks whether a file is hidden.
     * @param File $file
     * @return boolean true when the file is hidden, false otherwise
     */
    public function isHidden(File $file) {
        return substr($file->getName(), 0, 1) == '.';
    }

    /**
     * Check whether a has an absolute path
     * @param File $file
     * @return boolean true when the file has an absolute path
     */
    public function isAbsolute(File $file) {
        $path = $file->getPath();

        if ($file->hasPharProtocol()) {
            $path = substr($path, 7);
        }

        return $path[0] == File::DIRECTORY_SEPARATOR;
    }

    /**
     * Get the absolute path for a file
     * @param File $file
     * @return string
     */
    public function getAbsolutePath(File $file) {
        $path = $file->getPath();

        $phar = $file->isInPhar();
        if ($phar) {
            $pharFile = $this->getFile(substr($path, strlen($phar->getPath()) + 7));
            $path = $phar->getPath() . File::DIRECTORY_SEPARATOR . $pharFile->getPath();
        }

        $hasPharProtocol = $file->hasPharProtocol($path);
        if ($hasPharProtocol) {
            $path = substr($path, 7);
        }

        if (!$this->isAbsolute($file)) {
            $path = getcwd() . File::DIRECTORY_SEPARATOR . $path;
        }

        $absolutePath = array();

        $parts = explode(File::DIRECTORY_SEPARATOR, $path);
        foreach ($parts as $part) {
            if ($part == '' || $part == '.') {
                continue;
            }

            if ($part == '..') {
                array_pop($absolutePath);

                continue;
            }

            array_push($absolutePath, $part);
        }

        $absolutePath = File::DIRECTORY_SEPARATOR . implode(File::DIRECTORY_SEPARATOR, $absolutePath);

        if ($phar || $hasPharProtocol) {
            $file = $this->getFile($absolutePath);

            $absolutePath = $file->getPath();
            if ($hasPharProtocol && !$file->hasPharProtocol() && $file->isPhar()) {
                $absolutePath = 'phar://' . $absolutePath;
            }
        }

        return $absolutePath;
    }

    /**
     * Get the parent of the provided file
     *
     * If you provide a path like /var/www/site, the parent will be /var/www
     * @param File $file
     * @return File the parent of the file
     */
    public function getParent(File $file) {
        $path = $file->getPath();

        if (strpos($path, File::DIRECTORY_SEPARATOR) === false) {
            $parent = $this->getFile('.');

            return $this->getFile($this->getAbsolutePath($parent));
        }

        $name = $file->getName();
        $nameLength = strlen($name);

        $parent = substr($path, 0, ($nameLength + 1) * -1);
        if (!$parent) {
            return $this->getFile(File::DIRECTORY_SEPARATOR);
        }

        return $this->getFile($parent);
    }

}
