<?php

namespace ride\library\system;

use ride\library\system\exception\SystemException;
use ride\library\system\file\UnixFileSystem;
use ride\library\system\file\WindowsFileSystem;

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

    public function testGetFileSystemOnUnix() {
        $system = $this->getMock('ride\\library\\system\\System', array('isUnix', 'isWindows'));
        $system->expects($this->any())->method('isUnix')->will($this->returnValue(true));
        $system->expects($this->any())->method('isWindows')->will($this->returnValue(false));

        $fs = $system->getFileSystem();
        $fs2 = $system->getFileSystem();

        $this->assertTrue($fs instanceof UnixFileSystem);
        $this->assertTrue($fs === $fs2);
    }

    public function testGetFileSystemOnWindows() {
        $system = $this->getMock('ride\\library\\system\\System', array('isUnix', 'isWindows'));
        $system->expects($this->any())->method('isUnix')->will($this->returnValue(false));
        $system->expects($this->any())->method('isWindows')->will($this->returnValue(true));

        $fs = $system->getFileSystem();

        $this->assertTrue($fs instanceof WindowsFileSystem);
    }

    /**
     * @expectedException ride\library\system\exception\SystemException
     */
    public function testGetFileSystemOnUnsupportedSystemThrowsException() {
        $system = $this->getMock('ride\\library\\system\\System', array('isUnix', 'isWindows'));
        $system->expects($this->any())->method('isUnix')->will($this->returnValue(false));
        $system->expects($this->any())->method('isWindows')->will($this->returnValue(false));

        $system->getFileSystem();
    }

    public function testGetClientInCli() {
        $system = $this->getMock('ride\\library\\system\\System', array('isCli'));
        $system->expects($this->any())->method('isCli')->will($this->returnValue(true));

        $_SERVER['USER'] = 'user';
        $_SERVER['LOGNAME'] = null;

        $this->assertEquals('user', $system->getClient());

        $_SERVER['USER'] = null;
        $_SERVER['LOGNAME'] = 'logName';

        $this->assertEquals('logName', $system->getClient());

        $_SERVER['LOGNAME'] = null;

        $this->assertEquals('unknown', $system->getClient());
    }

    public function testGetClientInHttp() {
        $system = $this->getMock('ride\\library\\system\\System', array('isCli'));
        $system->expects($this->any())->method('isCli')->will($this->returnValue(false));

        $_SERVER['HTTP_CLIENT_IP'] = 'clientIp';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = null;
        $_SERVER['REMOTE_ADDR'] = null;

        $this->assertEquals('clientIp', $system->getClient());

        $_SERVER['HTTP_CLIENT_IP'] = null;
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'xForwardedFor';

        $this->assertEquals('xForwardedFor', $system->getClient());

        $_SERVER['HTTP_X_FORWARDED_FOR'] = null;
        $_SERVER['REMOTE_ADDR'] = 'remoteAddr';

        $this->assertEquals('remoteAddr', $system->getClient());

        $_SERVER['REMOTE_ADDR'] = null;

        $this->assertEquals('unknown', $system->getClient());
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
     * @expectedException ride\library\system\exception\SystemException
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