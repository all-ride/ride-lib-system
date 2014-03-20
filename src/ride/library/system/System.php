<?php

namespace ride\library\system;

use ride\library\system\exception\SystemException;
use ride\library\system\file\File;
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
     * Executes a command or multiple commands on the system
     * @param string|array $command Command string or array of command strings
     * @param integer $code Return code of the command
     * @return array Output of the command(s)
     * @throws ride\library\system\exception\SystemException when the command
     * could not be executed
     */
    public function execute($command, &$code = null) {
        if (is_array($command)) {
            return $this->executeCommands($command, $code);
        } else {
            return $this->executeCommand($command, $code);
        }
    }

    /**
     * Executes a command or multiple commands on the system in the user's
     * environment
     * @param string|array $command Command string or array of command strings
     * @param integer $code Return code of the command
     * @return array Output of the command(s)
     * @throws ride\library\system\exception\SystemException when the command
     * could not be executed
     */
    public function executeInShell($commands, &$code = null) {
        if (!$this->isUnix()) {
            throw new SystemException('Could not execute commands in shell: only supported on *nix systems');
        }

        $user = trim(shell_exec('whoami'));
        $home = trim(shell_exec('cd ~' . $user . ' && pwd'));

        $commands = array_merge(array(
            "export HOME=" . $home,
            "export DISPLAY=:0",
        ), (array) $commands);

        return $this->executeCommands($commands, $code);
    }

    /**
     * Executes a single command on the system
     * @param string $command Command string
     * @param integer $code Return code of the command
     * @return array Output of the command
     * @throws ride\library\system\exception\SystemException when the command
     * could not be executed
     */
    protected function executeCommand($command, &$code = null) {
        if (!is_string($command) || $command == '') {
            throw new SystemException('Could not execute command: provided command is empty or not a string');
        }

        $output = array();

        exec($command, $output, $code);

        if ($code == 127) {
            throw new SystemException('Could not execute ' . $command . ': command not found');
        }

        return $output;
    }

    /**
     * Executes multiple commands through a generated script
     * @param array $commands Array of command strings
     * @param integer $code Return code of the generated script
     * @return array Output of the generated script
     * @throws ride\library\system\exception\SystemException when the commands
     * could not be executed
     */
    protected function executeCommands(array $commands, $code = null) {
        $fileSystem = $this->getFileSystem();

        $fileScript = $fileSystem->getTemporaryFile();
        $fileLog = $fileScript->getParent()->getChild($fileScript->getName() . '.out');

        $command = $this->generateCommand($commands, $fileScript, $fileLog);
        $commandException = null;

        try {
            $this->executeCommand($command);

            if ($fileLog->exists()) {
                $output = trim($fileLog->read());

                if ($output) {
                    $output = explode("\n", $output);
                }
            } else {
                $output = array();
            }
        } catch (SystemException $exception) {
            $commandException = $exception;
        }

        if ($fileScript->exists()) {
            $fileScript->delete();
        }

        if ($fileLog->exists()) {
            $fileLog->delete();
        }

        if ($commandException) {
            throw $commandException;
        }

        return $output;
    }

    /**
     * Generates a command to execute multiple commands at once in the same
     * context
     * @param array $commands Array with command strings
     * @param \ride\library\system\file\File $fileScript Temporary file to
     * generate a script into
     * @param \ride\library\system\file\File $fileScript Temporary file to
     * catch the output of the script
     * @return string Command which executes the provided commands
     * @throws ride\library\system\exception\SystemException when no valid
     * commands are provided or when not supported for the current system
     */
    protected function generateCommand(array $commands, File $fileScript, File $fileLog) {
        if (!$this->isUnix()) {
            throw new SystemException('Could not generate command: only supported for *nix systems');
        }

        $script = '';
        foreach ($commands as $command) {
            $command = trim($command);
            if (!$command) {
                continue;
            }

            $script .= "echo \"# executing command: " . $command . "\"\n" . $command;
            if (substr($command, -1) != '&') {
                $script .= " || exit $?";
            }

            $script .= "\n";
        }

        if (!$script) {
            throw new SystemException('Could not generate script: no valid commands provided');
        }

        $script = "#/bin/sh\n\n" . $script;

        $fileScript->write($script);

        return 'sh ' . $fileScript . ' >> ' . $fileLog . ' 2>> ' . $fileLog;
    }

}