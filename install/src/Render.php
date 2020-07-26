<?php

declare(strict_types=1);

namespace Install;

/**
 * Class Render
 *
 * @package Install
 * @version 1.0
 * @author AkioSarkiz
 */
class Render
{
    public const DEFAULT_NAME_LAYOUT = 'base';

    public const DEFAULT_CONTENT_TAG = '{{ template }}';

    protected static $env = [];

    /**
     * Render the template. If not exists then return null.
     *
     * @param string $templateName name of page
     * @param array $variables
     * @return string|null
     */
    public static function html($templateName, $variables = []): ?string
    {
        self::$env = array_merge($variables, Installer::$lang ? ['lang_arr' => Installer::$lang] : []);
        return self::simpleRender($templateName);
    }

    /**
     * Check exists template in current directory
     *
     * @param string $templateName name of page
     * @return bool
     */
    protected static function existsTemplate($templateName): bool
    {
        return file_exists(__DIR__ . '/../views/templates/' . $templateName . '.html');
    }

    /**
     * Get content of template
     *
     * @param $templateName
     * @return string|null
     */
    protected static function getContentTemplate($templateName): ?string
    {
        $content = file_get_contents(__DIR__ . '/../views/templates/' . $templateName . '.html');
        return $content !== false ? $content : null;
    }

    /**
     * Get content of layout
     *
     * @param $layoutName
     * @return string|null
     */
    protected static function getContentLayout($layoutName): ?string
    {
        $content = file_get_contents(__DIR__ . '/../views/layouts/' . $layoutName . '.html');
        return $content !== false ? $content : null;
    }

    /**
     * Simple render page
     *
     * @param string $templateName name of page
     * @param string $layoutName name of layout
     * @return string|null
     */
    protected static function simpleRender($templateName, $layoutName = self::DEFAULT_NAME_LAYOUT): ?string
    {
        if (self::existsTemplate($templateName)) {
            $content = self::getContentTemplate($templateName);
            $layout = self::getContentLayout($layoutName);
            $layout = str_replace(self::DEFAULT_CONTENT_TAG, $content, $layout);

            /**
             * Parse variable in layout
             */
            preg_match_all('/{{\s*([0-9A-z_\-\'\"]+)\s*}}/u', $layout, $matches);

            if (count($matches) > 0) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $rawVar = $matches[0][$i];
                    $nameVar = $matches[1][$i];
                    $layout = str_replace($rawVar, self::simpleParseVar($nameVar), $layout);
                }
            }

            return $layout;
        }
        return null;
    }

    /**
     * Simple parse value
     *
     * @param $nameVar
     * @return string|null
     */
    protected static function simpleParseVar($nameVar): ?string
    {
        // Check array var
        preg_match_all('/\[[\'|"]([\w+0-9-_]+)[\'|\"]\]/u', $nameVar, $matches);

        if (count($matches[0]) > 0) {
            // as array

            preg_match('/\w+/u', $nameVar, $nameVar);
            $nameVar = $nameVar[0];

            $keys = [];
            foreach ($matches[1] as $match) {
                array_push($keys, $match);
            }

            if (array_key_exists($nameVar, self::$env)) {
                $subjectArray = self::$env[$nameVar];

                if (self::existsMultiKey($keys, $subjectArray)) {
                    $value = self::getMultiKey($keys, $subjectArray);
                    return self::simpleCommitValue($value);
                } else {
                    return $nameVar;
                }
            }
        } else {
            // as key

            if (array_key_exists($nameVar, self::$env)) {
                return self::simpleCommitValue(self::$env[$nameVar]);
            }

            return self::simpleCommitValue($nameVar);
        }

        return 'err_none';
    }

    /**
     * @param $value
     * @return string
     */
    protected static function simpleCommitValue($value): string
    {
        if (gettype($value) === 'string') {
            return $value;
        }
        return gettype($value);
    }

    /**
     * Check if specific array key exists in multidimensional array
     *
     * @param array $arr
     * @param array $keys
     * @param int $i
     * @return bool
     */
    protected static function existsMultiKey(array $keys, array $arr = null, $i = 0): bool
    {
        if ($arr === null) {
            $arr = self::$env;
        }

        if (key_exists($keys[$i], $arr)) {
            if (count($keys) === $i + 1) {
                return true;
            }

            return self::existsMultiKey($keys, $arr[$keys[$i]], $i + 1);
        }

        return false;
    }

    /**
     *  Get specific array key
     *
     * @param array $arr
     * @param array $keys
     * @param int $i
     * @return array|null
     */
    protected static function getMultiKey(array $keys, array $arr = null, $i = 0)
    {
        if ($arr === null) {
            $arr = self::$env;
        }

        if (key_exists($keys[$i], $arr)) {
            if (count($keys) === $i + 1) {
                return $arr[$keys[$i]];
            }

            return self::getMultiKey($keys, $arr[$keys[$i]], $i + 1);
        }

        return null;
    }
}
