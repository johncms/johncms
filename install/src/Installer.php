<?php

class Installer
{
    public static $lang = null;

    /**
     * Is installed system or not
     *
     * @return bool
     */
    public static function checkIsInstall(): bool
    {
        return is_file(__DIR__ . '/../../config/autoload/database.local.php') || is_file(__DIR__ . '/../../config/autoload/system.local.php');
    }

    /**
     * Get current lang
     *
     * @return string
     */
    public static function getLang(): string
    {
        if (isset($_POST['lng']) && ($_POST['lng'] == 'ru' || $_POST['lng'] == 'en')) {
            $_SESSION['language'] = $_POST['lng'];
        }
        return $language = $_SESSION['language'] ?? 'en';
    }

    /**
     * Get current lang file
     *
     * @return string
     */
    public static function getLangFile(): string
    {
        return __DIR__ . '/../locale/' . self::getLang() . '/install.php';
    }

    /**
     * Load lang of gui
     *
     * @return void
     */
    public static function loadLang(): void
    {
        if (self::$lang === null) {
            self::$lang = require self::getLangFile();
        }
    }

    /**
     * Критические ошибки настройки PHP
     *
     * @return array|bool
     */
    public static function checkPhpErrors()
    {
        if (! class_exists(PDO::class)) {
            $error[] = 'PDO';
        }

        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $error[] = 'Imagick or GD';
        }

        if (! extension_loaded('zlib')) {
            $error[] = 'zlib';
        }

        if (! extension_loaded('mbstring')) {
            $error[] = 'mbstring';
        }

        return ! empty($error) ? $error : false;
    }

    /**
     * Некритические предупреждения настройки PHP
     *
     * @return array|bool
     */
    public static function checkPhpWarnings()
    {
        $error = [];
        if (ini_get('register_globals')) {
            $error[] = 'register_globals';
        }

        return ! empty($error) ? $error : false;
    }

    /**
     * Проверяем права доступа к папкам
     *
     * @return array|bool
     */
    public static function checkFoldersRights()
    {
        $folders = [
            '/data/cache/',
            '/upload/downloads/files/',
            '/upload/downloads/screen/',
            '/upload/forum/attach/',
            '/upload/forum/topics/',
            '/upload/library/',
            '/upload/library/tmp',
            '/upload/library/images',
            '/upload/library/images/big',
            '/upload/library/images/orig',
            '/upload/library/images/small',
            '/upload/users/album/',
            '/upload/users/avatar/',
            '/upload/users/photo/',
            '/upload/mail/',
            '/config/autoload/',
        ];
        $error = [];

        foreach ($folders as $val) {
            if (! is_writable('..' . $val)) {
                $error[] = $val;
            }
        }

        return ! empty($error) ? $error : false;
    }

    /**
     * Проверяем права доступа к файлам
     *
     * @return array|bool
     */
    public static function checkFilesRights()
    {
        $error = [];

        if (is_file('../config/autoload/database.local.php') && ! is_writable('../config/autoload/database.local.php')) {
            $error[] = '/config/autoload/database.local.php';
        }

        return ! empty($error) ? $error : false;
    }

    /**
     * Парсинг SQL файла
     *
     * @param $file
     * @param PDO $pdo
     * @return array
     */
    public static function parseSql($file, PDO $pdo)
    {
        $errors = [];
        if ($file && file_exists($file)) {
            $query = fread(fopen($file, 'r'), filesize($file));
            $query = trim($query);
            $query = preg_replace("/\n\#[^\n]*/", '', "\n" . $query);
            $buffer = [];
            $ret = [];
            $in_string = false;
            for ($i = 0; $i < strlen($query) - 1; $i++) {
                if ($query[$i] == ';' && ! $in_string) {
                    $ret[] = substr($query, 0, $i);
                    $query = substr($query, $i + 1);
                    $i = 0;
                }
                if ($in_string && ($query[$i] == $in_string) && $buffer[1] != '\\') {
                    $in_string = false;
                } elseif (! $in_string && ($query[$i] == '"' || $query[$i] == "'") && (! isset($buffer[0]) || $buffer[0] != '\\')) {
                    $in_string = $query[$i];
                }
                if (isset($buffer[1])) {
                    $buffer[0] = $buffer[1];
                }
                $buffer[1] = $query[$i];
            }
            if (! empty($query)) {
                $ret[] = $query;
            }
            for ($i = 0; $i < count($ret); $i++) {
                $ret[$i] = trim($ret[$i]);
                if (! empty($ret[$i]) && $ret[$i] != '#') {
                    try {
                        $pdo->query($ret[$i]);
                    } catch (PDOException $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
        } else {
            $errors[] = 'ERROR: SQL file';
        }

        return $errors;
    }

    /**
     * Install CMS
     *
     * @return void
     */
    public static function install(): void
    {
        require __DIR__ . '/install_script.php';
    }
}
