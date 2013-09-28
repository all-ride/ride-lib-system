<?php

namespace pallo\library\system\file;

use pallo\library\system\System;

use \PHPUnit_Framework_TestCase;

class FileSystemTest extends PHPUnit_Framework_TestCase {

    /**
     * @var pallo\library\system\file\FileSystem
     */
    protected $fs;

    protected function setUp() {
        $system = new System();

        $this->fs = $system->getFileSystem();
    }

    public function testGetTemporaryFile() {
        $file = $this->fs->getTemporaryFile();

        $this->assertTrue($file->isWritable());

        $file->delete();
    }

    /**
     * @dataProvider providerExists
     */
    public function testExists($expected, $value) {
        $file = $this->fs->getFile($value);

        $result = $this->fs->exists($file);

        $this->assertEquals($expected, $result);
    }

    public function providerExists() {
        return array(
           array(true, __FILE__),
           array(false, 'unexistant'),
        );
    }

    /**
     * @dataProvider providerIsDirectory
     */
    public function testIsDirectory($expected, $value) {
        $file = $this->fs->getFile($value);

        $result = $this->fs->isDirectory($file);

        $this->assertEquals($expected, $result, $value);
    }

    public function providerIsDirectory() {
        return array(
           array(true, __DIR__),
           array(false, '/etc/passwd'),
           array(true, '.'),
           array(false, '/unexistant'),
        );
    }

    /**
     * @dataProvider providerIsReadable
     */
    public function testIsReadable($expected, $value) {
        if ($expected === false && file_exists($value)) {
            chmod($value, 0);
        }

        $file = $this->fs->getFile($value);

        $result = $this->fs->isReadable($file);

        $this->assertEquals($expected, $result, $value);
    }

    public function providerIsReadable() {
        return array(
           array(false, tempnam(sys_get_temp_dir(), 'file-system-test')),
           array(true, __FILE__),
           array(false, '/unexistant'),
        );
    }

    /**
     * @dataProvider providerIsWritable
     */
    public function testIsWritable($expected, $value) {
        if ($expected == false && file_exists($value)) {
            chmod($value, 0);
        }

        $file = $this->fs->getFile($value);

        $result = $this->fs->isWritable($file);

        $this->assertEquals($expected, $result, $value);
    }

    public function providerIsWritable() {
        return array(
           array(true, __FILE__),
           array(true, __DIR__),
           array(true, __DIR__ . '/unexistant'),
           array(false, '/unexistant'),
        );
    }

    public function testGetModificationTime() {
        $file = $this->fs->getFile('/tmp/test.txt');

        $this->fs->write($file, 'test');
        $now = time();

        $time = $file->getModificationTime();

        $this->assertEquals($now, $time);

        $this->fs->delete($file);
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testGetModificationTimeThrowsExceptionWhenFileDoesNotExist() {
        $file = $this->fs->getFile('unexistant');

        $this->fs->getModificationTime($file);
    }

    public function testGetSize() {
        $file = tempnam(sys_get_temp_dir(), 'file-system-test');
        file_put_contents($file, '0123456789');

        $file = $this->fs->getFile($file);

        $size = $file->getSize();

        $this->assertEquals('10', $size);
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testGetSizeThrowsExceptionWhenFileDoesNotExist() {
        $file = $this->fs->getFile('unexistant');

        $this->fs->getSize($file);
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testGetSizeThrowsExceptionWhenFileIsDirectory() {
        $file = $this->fs->getFile(__DIR__);

        $this->fs->getSize($file);
    }

    /**
     * @dataProvider providerGetPermissions
     */
    public function testGetPermissions($expected, $value) {
        chmod($value, $expected);

        $file = $this->fs->getFile($value);

        $result = $file->getPermissions();

        $this->assertEquals($expected, $result);

        unlink($value);
    }

    public function providerGetPermissions() {
        $writable = tempnam(sys_get_temp_dir(), 'file-system-test');
        chmod($writable, 0777);

        $unreadable = tempnam(sys_get_temp_dir(), 'file-system-test');
        chmod($writable, 000);

        return array(
           array(0777, $writable),
           array(0000, $unreadable),
        );
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testGetPermissionsThrowsExceptionWhenFileDoesNotExist() {
        $file = $this->fs->getFile('unexistant');

        $this->fs->getPermissions($file);
    }

    public function testSetPermissions() {
        $file = tempnam(sys_get_temp_dir(), 'file-system-test');
        chmod($file, 0777);

        $writable = $this->fs->getFile($file);

        $permissions = 0700;

        $writable->setPermissions($permissions);

        $this->assertEquals($permissions, $this->fs->getPermissions($writable));

        unlink($file);
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testSetPermissionsThrowsExceptionWhenFileDoesNotExist() {
        $file = $this->fs->getFile('unexistant');

        $this->fs->setPermissions($file, 0644);
    }

    public function testReadWithFile() {
        $data = '0123456789';
        $file = tempnam(sys_get_temp_dir(), 'file-system-test');
        file_put_contents($file, $data);

        $file = $this->fs->getFile($file);

        $content = $file->read();

        $this->assertEquals($data, $content);
    }

    public function testReadWithDirectory() {
        $basePath = __DIR__ . '/../../../../../..';

        $dir = $this->fs->getFile($basePath);
        $expectations = array(
            $basePath . '/src',
            $basePath . '/test',
        );

        $content = $dir->read();
        foreach ($expectations as $expected) {
            $this->assertArrayHasKey($expected, $content, $expected);
            $this->assertEquals($expected, $content[$expected]->getPath());
        }
    }

    public function testReadRecursiveWithDirectory() {
        $basePath = __DIR__ . '/../../../../../..';

        $dir = $this->fs->getFile($basePath);
        $expectations = array(
            $basePath . '/src/pallo/library/system/exception/FileSystemException.php',
            $basePath . '/src/pallo/library/system/file/File.php',
            $basePath . '/test/src/pallo/library/system/file/FileTest.php',
        );

        $content = $this->fs->read($dir, true);

        foreach ($expectations as $expected) {
            $this->assertArrayHasKey($expected, $content, $expected);
            $this->assertEquals($expected, $content[$expected]->getPath());
        }
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testReadThrowsExceptionWhenFileDoesNotExist() {
        $file = $this->fs->getFile('unexistant');

        $this->fs->read($file);
    }

    public function testWrite() {
        $file = $this->fs->getFile('/tmp/test.txt');

        $content = 'content';

        $file->write($content);

        $this->assertEquals($content, $this->fs->read($file));

        $append = 'appended content';

        $this->fs->write($file, $append, true);

        $this->assertEquals($content . $append, $this->fs->read($file));

        $this->fs->delete($file);
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testWriteThrowsExceptionWhenFileIsNotWritable() {
        $file = tempnam(sys_get_temp_dir(), 'file-system-test');
        chmod($file, 0000);

        $file = $this->fs->getFile($file);

        $this->fs->write($file, 'content');
    }

    public function testCreate() {
        $file = $this->fs->getFile(sys_get_temp_dir());
        $file = $file->getChild('testCreate');

        $this->fs->create($file);
        $this->fs->create($file);

        $this->assertTrue($this->fs->isDirectory($file));
    }

    public function testCreateRecursive() {
        $file = $this->fs->getFile(sys_get_temp_dir());
        $file = $file->getChild('testCreateRecursive');
        $child = $file->getChild('inner/directory');

        $this->fs->create($child);

        $this->assertTrue($this->fs->isDirectory($child));
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testCreateThrowsExceptionWhenFileIsNotWritable() {
        $file = $this->fs->getFile('/not_writable');

        $this->fs->create($file);
    }

    public function testDeleteDirectory() {
        $dir = $this->fs->getFile('/tmp/test');

        if (!$this->fs->exists($dir)) {
            $dir->create();
        }

        $this->fs->delete($dir);

        $this->assertFalse($this->fs->exists($dir));
    }

    public function testDeleteDirectoryWithSubDirectories() {
        $dir = $this->fs->getFile('/tmp/test');
        $innerDir = $dir->getChild('inner/directory');

        if (!$this->fs->exists($innerDir)) {
            $this->fs->create($innerDir);
        }

        $this->fs->delete($dir);

        $this->assertFalse($this->fs->exists($dir));
    }

    public function testDeleteFile() {
        $file = tempnam(sys_get_temp_dir(), 'file-system-test');

        $file = $this->fs->getFile($file);

        $this->assertTrue($this->fs->exists($file));

        $this->fs->delete($file);

        $this->assertFalse($this->fs->exists($file));
    }

    public function testCopyFile() {
        $source = $this->fs->getFile('/tmp/pallo/source');
        $this->fs->write($source, 'contents');

        $destination = $this->fs->getFile('/tmp/pallo/source.copy');
        if ($destination->exists()) {
            $destination->delete();
        }

        $source->copy($source);

        $this->fs->copy($source, $destination);

        $this->assertTrue($this->fs->exists($destination), 'destination does not exist');
        $this->assertEquals($this->fs->getSize($source), $this->fs->getSize($destination), 'source and destination are not the same size');

        $this->fs->delete($source);
        $this->fs->delete($destination);
    }

    public function testCopyDirectory() {
        $source = $this->fs->getFile('/tmp/test');
        $destination = $this->fs->getFile('/tmp/test2');
        $innerDir = 'inner/directory';
        $sourceInnerDir = $source->getChild($innerDir);
        $destinationInnerDir = $destination->getChild($innerDir);

        if (!$this->fs->exists($sourceInnerDir)) {
            $this->fs->create($sourceInnerDir);
        }
        if ($this->fs->exists($destination)) {
            $this->fs->delete($destination);
        }

        $this->fs->copy($source, $destination);

        $this->assertTrue($this->fs->exists($destinationInnerDir), 'destination does not exist');

        $this->fs->delete($source);
        $this->fs->delete($destination);
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testCopyWithUnexistingFileThrowsException() {
        $source = $this->fs->getFile('unexistant');
        $destination = $this->fs->getFile('unexistant.copy');

        $this->fs->copy($source, $destination);
    }

    public function testMoveFile() {
        $source = $this->fs->getFile('/tmp/pallo/source');
        $this->fs->write($source, 'contents');

        $sourceSize = $this->fs->getSize($source);

        $destination = $this->fs->getFile('/tmp/pallo/source.move');
        if ($this->fs->exists($destination)) {
            $this->fs->delete($destination);
        }

        $source->move($destination);

        $this->assertTrue($this->fs->exists($destination), 'destination does not exist');
        $this->assertFalse($this->fs->exists($source), 'source does still exist');
        $this->assertEquals($sourceSize, $this->fs->getSize($destination), 'source and destination are not the same size');

        $this->fs->delete($destination);
    }

    public function testMoveDirectory() {
        $source = $this->fs->getFile('/tmp/test');
        $destination = $this->fs->getFile('/tmp/test2');
        $innerDir = 'inner/directory';
        $sourceInnerDir = $source->getChild($innerDir);
        $destinationInnerDir = $destination->getChild($innerDir);

        if (!$this->fs->exists($sourceInnerDir)) {
            $this->fs->create($sourceInnerDir);
        }

        $this->assertFalse($this->fs->exists($destinationInnerDir), 'destination already exists');

        $this->fs->move($source, $destination);

        $this->assertTrue($this->fs->exists($destinationInnerDir), 'destination does not exist');
        $this->assertFalse($this->fs->exists($source), 'source does still exist');

        $this->fs->delete($destination);
    }

    /**
     * @expectedException pallo\library\system\exception\FileSystemException
     */
    public function testMoveWithUnexistingFileThrowsException() {
        $source = $this->fs->getFile('unexistant');
        $destination = $this->fs->getFile('unexistant.move');

        $this->fs->move($source, $destination);
    }

}