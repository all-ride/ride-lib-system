<?php

namespace pallo\library\system;

use pallo\library\system\exception\SystemException;
use pallo\library\system\file\UnixFileSystem;
use pallo\library\system\file\WindowsFileSystem;

/**
 * System functions
 */
class System {

	/**
	 * Instance of the file system
	 * @var pallo\library\system\file\FileSystem
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
     * @return \pallo\library\system\pallo\library\system\file\FileSystem
     * @throws pallo\library\system\exception\Exception when the file
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
    		throw new SystemException('Could not get the file system: ' . $osType . ' is not supported');
    	}

    	return $this->fs;
    }

    /**
     * Executes a command on the system
     * @param string $command Command string
     * @param integer $code Return code of the command
     * @return array Output of the command
     * @throws pallo\library\system\exception\Exception when the provided
     * command is empty or not a string
     * @throws pallo\library\system\exception\Exception when the command could
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