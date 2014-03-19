<?php

namespace ride\library\system\file;

use ride\library\system\exception\FileSystemException;

use \PHPUnit_Framework_TestCase;

class PermissionConverterTest extends PHPUnit_Framework_TestCase {

    private $converter;

    public function setUp() {
        $this->converter = new PermissionConverter();
    }

    /**
     * @dataProvider providerTests
     */
    public function testConvertNumericToOctal($octal, $numeric, $rwx) {
       $result = $this->converter->convertNumericToOctal($numeric);
       $this->assertEquals($octal, $result);
    }

    /**
     * @dataProvider providerTests
     */
    public function testConvertNumericToRwx($octal, $numeric, $rwx) {
       $result = $this->converter->convertNumericToRwx($numeric);
       $this->assertEquals($rwx, $result);
    }

    /**
     * @dataProvider providerValidationTests
     */
    public function testValidateNumeric($result, $octal, $numeric, $rwx) {
        try {
            $this->converter->convertNumericToRwx($numeric);
            if (!$result) {
                $this->fail($rwx . ' should be invalid');
            }
        } catch (FileSystemException $exception) {
            if ($result) {
                $this->fail($exception->getMessage());
            }
        }
    }

    /**
     * @dataProvider providerTests
     */
    public function testConvertOctalToRwx($octal, $numeric, $rwx) {
       $result = $this->converter->convertOctalToRwx($octal);
       $this->assertEquals($rwx, $result);
    }

    /**
     * @dataProvider providerTests
     */
    public function testConvertOctalToNumeric($octal, $numeric, $rwx) {
       $result = $this->converter->convertOctalToNumeric($octal);
       $this->assertEquals($numeric, $result);
    }

    /**
     * @dataProvider providerValidationTests
     */
    public function testValidateOctal($result, $octal, $numeric, $rwx) {
        try {
            $this->converter->convertOctalToRwx($octal);
            if (!$result) {
                $this->fail($octal . ' should be invalid');
            }
        } catch (FileSystemException $exception) {
            if ($result) {
                $this->fail($exception->getMessage());
            }
        }
    }

    /**
     * @dataProvider providerTests
     */
    public function testConvertRwxToNumeric($octal, $numeric, $rwx) {
       $result = $this->converter->convertRwxToNumeric($rwx);
       $this->assertEquals($numeric, $result);
    }

    /**
     * @dataProvider providerTests
     */
    public function testConvertRwxToOctal($octal, $numeric, $rwx) {
       $result = $this->converter->convertRwxToOctal($rwx);
       $this->assertEquals($octal, $result);
    }

    /**
     * @dataProvider providerValidationTests
     */
    public function testValidateRwx($result, $octal, $numeric, $rwx) {
        try {
            $this->converter->convertRwxToOctal($rwx);
            if (!$result) {
                $this->fail($rwx . ' should be invalid');
            }
        } catch (FileSystemException $exception) {
            if ($result) {
                $this->fail($exception->getMessage());
            }
        }
    }

    public function providerTests() {
        return array(
            array(
                'octal' => '1204',
                'numeric' => 644,
                'rwx' => 'rw-r--r--',
            ),
            array(
                'octal' => '1244',
                'numeric' => 676,
                'rwx' => 'rw-rwxrw-',
            ),
            array(
                'octal' => '1130',
                'numeric' => 600,
                'rwx' => 'rw-------',
            ),
        );
    }

    public function providerValidationTests() {
        return array(
            array(
                'result' => true,
                'octal' => '1204',
                'numeric' => 644,
                'rwx' => 'rw-r--r--',
            ),
            array(
                'result' => false,
                'octal' => '1234',
                'numeric' => 6666,
                'rwx' => 'rw-rw-rf-',
            ),
            array(
                'result' => false,
                'octal' => '9999',
                'numeric' => 888,
                'rwx' => 'rw-rw-rw',
            ),
            array(
                'result' => false,
                'octal' => 'tester',
                'numeric' => 888,
                'rwx' => 'rw-rw-rw',
            ),
        );
    }

}
