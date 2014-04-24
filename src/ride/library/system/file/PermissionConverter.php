<?php

namespace ride\library\system\file;

use ride\library\system\exception\FileSystemException;

/**
 * Converter for file permissions from and to different formats
 */
class PermissionConverter {

    /**
     * Regular expression to check rwx permissions
     * @var string
     */
    const REGEX_RWX = '/^(r|w|x|-){9}$/';

    /**
     * Regular expression to check numeric permissions
     * @var string
     */
    const REGEX_NUMERIC = '/^[0-7]{3}$/';

    /**
     * Permission flag for no permissions
     * @var string
     */
    const NONE_FLAG = '-';

    /**
     * Permission value for no permissions
     * @var string
     */
    const NONE_VALUE = 0;

    /**
     * Permission flag of the execute permission
     * @var string
     */
    const EXECUTE_FLAG = 'x';

    /**
     * Permission value of the execute permission
     * @var string
     */
    const EXECUTE_VALUE = 1;

    /**
     * Permission flag of the write permission
     * @var string
     */
    const WRITE_FLAG = 'w';

    /**
     * Permission value of the write permission
     * @var string
     */
    const WRITE_VALUE = 2;

    /**
     * Permission flag of the read permission
     * @var string
     */
    const READ_FLAG = 'r';

    /**
     * Permission value of the read permission
     * @var string
     */
    const READ_VALUE = 4;

    /**
     * Converts numeric permissions to octal permissions
     * @param int $numericPermissions
     * @return int Octal permissions
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid numeric permissions value
     */
    public function convertNumericToOctal($numericPermissions) {
        $this->checkNumericPermissions($numericPermissions);

        return decoct($numericPermissions);
    }

    /**
     * Converts numeric permissions to permissions in the rwx format
     * @param int $numericPermissions
     * @return string Permissions in the rwx format
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid numeric permissions value
     */
    public function convertNumericToRwx($numericPermissions) {
        $this->checkNumericPermissions($numericPermissions);

        $rwxPermissions = '';

        for ($i = 0; $i < 3; $i++) {
            $value = substr($numericPermissions, $i, 1);

            if (($value / self::READ_VALUE) >= 1) {
                $rwxPermissions .= self::READ_FLAG;
                $value -= self::READ_VALUE;
            } else {
                $rwxPermissions .= self::NONE_FLAG;
            }

            if (($value / self::WRITE_VALUE) >= 1) {
                $rwxPermissions .= self::WRITE_FLAG;
                $value -= self::WRITE_VALUE;
            } else {
                $rwxPermissions .= self::NONE_FLAG;
            }

            if (($value / self::EXECUTE_VALUE) >= 1) {
                $rwxPermissions .= self::EXECUTE_FLAG;
            } else {
                $rwxPermissions .= self::NONE_FLAG;
            }
        }

        return $rwxPermissions;
    }

    /**
     * Convert octal permissions to permissions in the rwx format.
     * @param int $octalPermissions Octal permissions
     * @return string Permissions in the rwx format (eg. rwxr--r--)
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid octal permissions value
     */
    public function convertOctalToRwx($octalPermissions) {
        $numericPermissions = $this->convertOctalToNumeric($octalPermissions);

        return $this->convertNumericToRwx($numericPermissions);
    }

    /**
     * Converts octal permissions to the numeric permissions
     * @param int $octalPermissions Octal permissions
     * @return string Permissions in the numeric format (eg. 744)
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid octal permissions value
     */
    public function convertOctalToNumeric($octalPermissions) {
        $this->checkOctalPermissions($octalPermissions);

        return substr(octdec($octalPermissions), -3);
    }

    /**
     * Converts permissions in the rwx format to numeric permissions
     * @param string $rwxPermissions Permissions in the rwx format
     * (eg. rwxr-xr-x)
     * @return int Numeric permissions (eg. 755)
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid rwx permissions value
     */
    public function convertRwxToNumeric($rwxPermissions) {
        $this->checkRwxPermissions($rwxPermissions);

        $translation = array(
            self::NONE_FLAG => self::NONE_VALUE,
            self::EXECUTE_FLAG => self::EXECUTE_VALUE,
            self::WRITE_FLAG => self::WRITE_VALUE,
            self::READ_FLAG => self::READ_VALUE
        );

        $rwxPermissions = strtr($rwxPermissions, $translation);

        $numericPermissions = '';
        $numericPermissions .= $rwxPermissions[0] + $rwxPermissions[1] + $rwxPermissions[2];
        $numericPermissions .= $rwxPermissions[3] + $rwxPermissions[4] + $rwxPermissions[5];
        $numericPermissions .= $rwxPermissions[6] + $rwxPermissions[7] + $rwxPermissions[8];

        return $numericPermissions;
    }

    /**
     * Converts permissions in the rwx format to numeric permissions
     * @param string $rwxPermissions Permissions in the rwx format
     * (eg. rwxr-xr-x)
     * @return int Numeric permissions (eg. 755)
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid rwx permissions value
     */
    public function convertRwxToOctal($rwxPermissions) {
        $numericPermissions = $this->convertRwxToNumeric($rwxPermissions);

        return decoct($numericPermissions);
    }

    /**
     * Checks if the provided numeric permissions is a valid value
     * @param int $numericPermissions Numeric permissions
     * @return null
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid numeric permissions value
     */
    private function checkNumericPermissions($numericPermissions) {
        if (!preg_match(self::REGEX_NUMERIC, $numericPermissions)) {
            throw new FileSystemException('Provided permissions is not a valid numeric permissions value: value not between 000 and 777');
        }
    }

    /**
     * Checks if the provided octal permissions is a valid value
     * @param int $octalPermissions Octal permissions
     * @return null
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid octal permissions value
     */
    private function checkOctalPermissions($octalPermissions) {
        $lengthPermissions = strlen($octalPermissions);

        if ($lengthPermissions > 5) {
            throw new FileSystemException('Provided permissions is not a valid octal permissions value: too much digits');
        }
        if ($octalPermissions != decoct(octdec($octalPermissions))) {
            throw new FileSystemException('Provided permissions is not a valid octal permissions value: not an octal value');
        }
    }

    /**
     * Checks if the provided rwx permissions is a valid value
     * @param string $rwxPermissions Rwx permissions
     * @return null
     * @throws \ride\library\system\file\FileSystemException when the provided
     * permissions is not a valid rwx permissions value
     */
    private function checkRwxPermissions($rwxPermissions) {
        if (!preg_match(self::REGEX_RWX, $rwxPermissions)) {
            throw new FileSystemException('Provided permissions is not a valid rwx permissions value: only r, w, x and - are allowed and the length of the permissions should be 9 characters long.');
        }
    }

}
