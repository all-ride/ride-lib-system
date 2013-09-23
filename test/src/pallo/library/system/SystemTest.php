<?php

namespace pallo\library\system;

use pallo\library\system\exception\SystemException;
use pallo\library\system\file\UnixFileSystem;
use pallo\library\system\file\WindowsFileSystem;

use \PHPUnit_Framework_TestCase;

class SystemTest extends PHPUnit_Framework_TestCase {

	protected $system;

	public function setUp() {
		$this->system = new System();
	}

	public function testOs() {
		$os = strtoupper(PHP_OS);

		if ($os == 'LINUX' || $os == 'UNIX' || $os == 'DARWIN') {
			$this->assertTrue($this->system->isUnix());
			$this->assertFalse($this->system->isWindows());
		} elseif ($os == 'WIN32' || $os == 'WINNT') {
			$this->assertFalse($this->system->isUnix());
			$this->assertTrue($this->system->isWindows());
		} else {
			$this->assertFalse($this->system->isUnix());
			$this->assertFalse($this->system->isWindows());
		}
	}

	public function testCli() {
		if (PHP_SAPI == 'cli') {
			$this->assertTrue($this->system->isCli());
		} else {
			$this->assertFalse($this->system->isCli());
		}
	}

	public function testGetFileSystem() {
		if ($this->system->isUnix()) {
			$fs = $this->system->getFileSystem();

			$this->assertTrue($fs instanceof UnixFileSystem);
		} elseif ($this->system->isWindows()) {
			$fs = $this->system->getFileSystem();

			$this->assertTrue($fs instanceof UnixFileSystem);
		} else {
			try {
				$fs = $this->system->getFileSystem();
				$this->fail('no exception thrown for unsupported file system');
			} catch (SystemException $exception) {

			}
		}
	}

	public function testExecute() {
		$code = null;
		$expected = array(getcwd());

		$result = $this->system->execute('pwd', $code);

		$this->assertNotNull($code);
		$this->assertNotNull($result);
		$this->assertEquals(0, $code);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider providerExecuteThrowsExceptionOnInvalidCommand
	 * @expectedException pallo\library\system\exception\SystemException
	 */
	public function testExecuteThrowsExceptionOnInvalidCommand($command) {
		$this->system->execute($command);
	}

	public function providerExecuteThrowsExceptionOnInvalidCommand() {
		return array(
			array(array()),
			array($this),
			array('unexistant-command'),
		);
	}

}