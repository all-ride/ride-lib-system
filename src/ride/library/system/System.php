<?php

namespace ride\library\system;

use ride\library\system\exception\SystemException;
use ride\library\system\file\UnixFileSystem;
use ride\library\system\file\WindowsFileSystem;

/**
 * System functions
 */
class System {

    /**
     * Instance of the file system
     * @var ride\library\system\file\FileSystem
     */
    protected $fs;

    /**
     * Checks if the server operating system is a unix variant
     * @return boolean True when the server operating system is a unix variant,
     * false otherwise
     */
    public function isUnix() {
        $osType = strtoupper(PHP_OS);

        switch ($osType) {
            case 'LINUX':
            case 'UNIX':
            case 'DARWIN':
                return true;
            default:
                return false;
        }
    }

    /**
     * Checks if the server operating system is Microsoft Windows
     * @return boolean True when the server operating system is Microsoft
     * Windows, false otherwise
     */
    public function isWindows() {
        $osType = strtoupper(PHP_OS);

        switch ($osType) {
            case 'WIN32':
            case 'WINNT':
                return true;
            default:
                return false;
        }
    }

    /**
     * Gets the file system
     * @return \ride\library\system\ride\library\system\file\FileSystem
     * @throws ride\library\system\exception\Exception when the file
     * system is not supported
     */
    public function getFileSystem() {
        if ($this->fs) {
            return $this->fs;
        }

        if ($this->isUnix()) {
            $this->fs = new UnixFileSystem();
        } elseif ($this->isWindows()) {
            $this->fs = new WindowsFileSystem();
        } else {
            throw new SystemException('Could not get the file system: ' . PHP_OS . ' is not supported');
        }

        return $this->fs;
    }

    /**
     * Checks if the current environment is CLI
     * @return boolean
     */
    public function isCli() {
        return PHP_SAPI == 'cli' || isset($_SERVER['SHELL']);
    }

    /**
     * Gets the client who is using the system. When invoked through cli, the
     * user of the system is returned, an ip when invoked through the web
     * @return string
     */
    public function getClient() {
        if ($this->isCli()) {
            if (isset($_SERVER['USER'])) {
                return $_SERVER['USER'];
            } elseif (isset($_SERVER['LOGNAME'])) {
                return $_SERVER['LOGNAME'];
            }
        } else {
            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                return $_SERVER['REMOTE_ADDR'];
            }
        }

        return 'unknown';
    }

    /**
     * Executes a command on the system
     * @param string $command Command string
     * @param integer $code Return code of the command
     * @return array Output of the command
     * @throws ride\library\system\exception\Exception when the provided
     * command is empty or not a string
     * @throws ride\library\system\exception\Exception when the command could
     * not be executed
     */
    public function execute($command, &$code = null) {
        if (!is_string($command) || $command == '') {
            throw new SystemException('Could not execute command: provided command is empty or not a string');
        }

        $output = array();

        exec($command, $output, $code);

        if ($code == 127) {
            throw new SystemException('Could not execute ' . $command);
        }

        return $output;
    }

}