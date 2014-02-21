<?php

namespace ride\library\system\file;

use \PHPUnit_Framework_TestCase;

class WindowsFileSystemTest extends PHPUnit_Framework_TestCase {

    /**
     * @var ride\library\system\file\WindowsFileSystem
     */
    protected $fs;

    public function setUp() {
        $this->fs = new WindowsFileSystem();
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
           array(true, 'C:/'),
           array(false, '/C'),
           array(true, '/C/'),
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
           array(true, 'C:/tmp/test.txt'),
           array(true, 'C:\\tmp\\test.txt'),
           array(true, 'D:\\Documents\\test.txt'),
           array(true, '\\\\server\\path\\file.txt'),
           array(true, 'phar://C:/tmp/test.txt'),
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
           array(getcwd(), '.'),
           array(getcwd() . '/test/test.txt', 'test/.././test/.//./test.txt'),
           array(getcwd() . '/test/test.txt', getcwd() . '/test/.././test/.//./test.txt'),
           array('phar://' . getcwd() . '/modules/test.phar', 'phar://modules/test.phar'),
           array('phar://D:/modules/test.phar/file.txt', 'D:/modules/test.phar/file.txt'),
        );
    }

    /**
     * @dataProvider providerGetParent
     */
    public function testGetParent($expected, $value) {
        $file = $this->fs->getFile($value);

        $this->assertEquals($this->fs->getFile($expected), $this->fs->getParent($file));
    }

    public function providerGetParent() {
        return array(
           array('test', 'test/test.txt'),
           array(getcwd(), 'test.txt'),
           array('/', '/root'),
           array('/', '/'),
           array('C:/folder', 'C:/folder/test.txt'),
           array('C:/', 'C:/test.txt'),
           array('C:/', 'C:/'),
        );
    }

}
