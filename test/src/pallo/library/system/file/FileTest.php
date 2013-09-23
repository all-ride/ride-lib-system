<?php

namespace pallo\library\system\file;

use pallo\library\system\System;

use \PHPUnit_Framework_TestCase;

class FileTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var pallo\library\system\file\FileSystem
	 */
	protected $fs;

	protected function setUp() {
		$this->createFs();
	}

	protected function createFs() {
		$system = new System();

		$this->fs = $system->getFileSystem();
	}

    /**
     * @dataProvider providerConstruct
     */
    public function testConstruct($expected, $path) {
        $file = new File($this->fs, $path);

        $this->assertEquals($this->fs, $file->getFileSystem());
        $this->assertEquals($expected, $file->getPath());
        $this->assertEquals($expected, (string) $file);
    }

    public function providerConstruct() {
		$this->createFs();

        return array(
           array('test/test.txt', 'test/test.txt'),
           array('test/test.txt', new File($this->fs, 'test/test.txt')),
           array('test', 'test/'),
           array('test.phar', 'test.phar'),
           array('phar://test.phar/file.txt', 'test.phar/file.txt'),
           array('/', '/'),
        );
    }

    /**
     * @dataProvider providerGetFile
     */
    public function testGetFile($expected, $path, $child) {
        $file = new File($this->fs, $path);
        $file = $file->getChild($child);

        $this->assertEquals($expected, $file->getPath());
    }

    public function providerGetFile() {
		$this->createFs();

        return array(
           array('test/test.txt', 'test/', 'test.txt'),
           array('test/tester/test.txt', 'test/tester', 'test.txt'),
           array('test/tester/test.txt', 'test/tester', new File($this->fs, 'test.txt')),
           array('test/tester/test.txt', new File($this->fs, 'test/tester'), new File($this->fs, 'test.txt')),
           array('phar://test/test.phar/test/test.txt', 'test/test.phar', 'test/test.txt'),
           array('phar://test/test.phar/test/test.txt', 'phar://test/test.phar', 'test/test.txt'),
           array('phar://folder/test.phar/file.txt', 'folder', 'phar://test.phar/file.txt'),
        );
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testConstructWithInvalidPathThrowsException() {
        $file = new File($this->fs, '');
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testGetChildWithAbsoluteChildThrowsException() {
        $file = new File($this->fs, 'test/test');
        $file->getChild('/tmp/test.txt');
    }

    /**
     * @dataProvider providerGetName
     */
    public function testGetName($expected, $value) {
        $file = new File($this->fs, $value);
        $this->assertEquals($expected, $file->getName());
    }

    public function providerGetName() {
        return array(
           array('test.txt', 'test.txt'),
           array('test.txt', 'test/test.txt'),
        );
    }

    /**
     * @dataProvider providerIsRootPath
     */
    public function testIsRootPath($expected, $value) {
        $file = new File($this->fs, $value);

        $this->assertEquals($expected, $file->isRootPath());
    }

    public function providerIsRootPath() {
        return array(
           array(true, '/'),
           array(false, '/test'),
        );
    }

    /**
     * @dataProvider providerGetExtension
     */
    public function testGetExtension($expected, $value) {
        $file = new File($this->fs, $value);
        $this->assertEquals($expected, $file->getExtension());
    }

    public function providerGetExtension() {
        return array(
           array('txt', 'test.txt'),
           array('', 'test'),
           array('htaccess', '.htaccess'),
           array('zip', '.test.ZIP'),
           array('xml', '/test/config/modules.xml'),
           array('', './test/config'),
           array('', './test.folder/config'),
        );
    }

    /**
     * @dataProvider providerHasExtension
     */
    public function testHasExtension($expected, $value) {
        $file = new File($this->fs, 'test.txt');

        $result = $file->hasExtension($value);

        $this->assertEquals($expected, $result, var_export($value, true));
    }

    public function providerHasExtension() {
        return array(
           array(true, 'txt'),
           array(false, 'test'),
           array(false, array('png', 'jpg')),
           array(true, array('png', 'jpg', 'txt')),
        );
    }

    /**
     * @dataProvider providerIsReadable
     */
    public function testIsReadable($expected, $value) {
    	$file = new File($this->fs, $value);

    	$this->assertEquals($expected, $file->isReadable());
    }

    public function providerIsReadable() {
    	return array(
  			array(true, '/'),
   			array(false, 'unexistant'),
    	);
    }

    /**
     * @dataProvider providerIsInPhar
     */
    public function testIsInPhar($expected, $value) {
        $file = new File($this->fs, $value);

        $result = $file->isInPhar($file);

        $this->assertEquals($expected, $result);
    }

    public function providerIsInPhar() {
    	$this->createFs();

        return array(
           array(false, 'file.txt'),
           array(false, 'file.phar'),
           array(new File($this->fs, 'test.phar'), 'test.phar/file.txt'),
        );
    }

    /**
     * @dataProvider providerGetCopyFile
     */
    public function testGetCopyFile($expected, $value) {
        $file = new File($this->fs, $value);

        $copyFile = $file->getCopyFile($file);

        $this->assertEquals($expected, $copyFile->getPath(), $value);
    }

    public function providerGetCopyFile() {
        return array(
           array('/etc/passwd-1', '/etc/passwd'),
           array(__DIR__ . '/FileTest-1.php', __FILE__),
           array('test/unexistant.txt', 'test/unexistant.txt'),
        );
    }

}