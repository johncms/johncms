<?php

declare(strict_types=1);

namespace Tests\Unit\Files;

use Johncms\Files\FileInfo;
use PHPUnit\Framework\TestCase;

class FileInfoTest extends TestCase
{
    public function testGetNameWithoutExtension()
    {
        $names = [
            'Тестовый файл.txt' => 'Тестовый файл',
            'file.name.gif.txt' => 'file.name.gif',
            'filename.'         => 'filename',
            '.filename'         => '.filename',
        ];
        foreach ($names as $original => $clear) {
            $fileInfo = new FileInfo($original);
            $this->assertEquals($clear, $fileInfo->getNameWithoutExtension());
        }
    }

    public function testGetCleanName()
    {
        $names = [
            'Тестовый файл.txt'  => 'Testovyi_fail.txt',
            'Рƒі∆l‘ѓ•ƒ_file.txt' => 'Rfil_gjf_file.txt',
            'test_1.sxt.txt'     => 'test_1_sxt.txt',
        ];

        foreach ($names as $original => $clear) {
            $fileInfo = new FileInfo($original);
            $this->assertEquals($clear, $fileInfo->getCleanName());
        }
    }
}
