<?php

namespace pallo\library\system\file;

use \PHPUnit_Framework_TestCase;

class UnixFileSystemTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var pallo\library\system\file\WindowsFileSystem
	 */
	protected $fs;

	public function setUp() {
		$this->fs = new UnixFileSystem();
	}

    /**
     * @dataProvider providerIsRootPath
     */
    public function testIsRootPath($expected, $value) {
        $result = $this->fs->isRootPath($value);

        $this->assertEquals($expected, $result, $value);
    }

    public function providerIsRootPath() {
        return array(
           array(false, 'test/test.txt'),
           array(false, 'test.txt'),
           array(true, '/'),
           array(false, ''),
        );
    }

    /**
     * @dataProvider providerIsAbsolute
     */
    public function testIsAbsolute($expected, $value) {
        $result = $this->fs->isAbsolute($this->fs->getFile($value));

        $this->assertEquals($expected, $result, $value);
    }

    public function providerIsAbsolute() {
        return array(
           array(false, 'test/test.txt'),
           array(false, 'test.txt'),
           array(true, '/tmp/test.txt'),
           array(true, 'phar:///tmp/test.txt'),
           array(false, 'phar://test.phar/tmp/test.txt'),
        );
    }

    /**
     * @dataProvider providerGetAbsolutePath
     */
    public function testGetAbsolutePath($expected, $value) {
        $result = $this->fs->getAbsolutePath($this->fs->getFile($value));

        $this->assertEquals($expected, $result, $value);
    }

    public function providerGetAbsolutePath() {
        return array(
           array(getcwd() . '/test/test.txt', 'test/.././test/.//./test.txt'),
           array(getcwd() . '/test/test.txt', getcwd() . '/test/.././test/.//./test.txt'),
           array('phar://' . getcwd() . '/modules/test.phar', 'phar://modules/test.phar'),
           array('phar://' . getcwd() . '/modules/test.phar/file.txt', 'modules/test.phar/file.txt'),
        );
    }

    /**
     * @dataProvider providerGetParent
     */
    public function testGetParent($expected, $value) {
        $file = $this->fs->getFile($value);

        $this->assertEquals($expected, $this->fs->getParent($file));
    }

    public function providerGetParent() {
    	$fs = new UnixFileSystem();

        return array(
           array(new File($fs, 'test'), 'test/test.txt'),
           array(new File($fs, getcwd()), 'test.txt'),
           array(new File($fs, '/'), '/root'),
           array(new File($fs, '/'), '/'),
        );
    }

}