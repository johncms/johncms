<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Admin\Languages;

use ZipArchive;

class Languages
{
    /**
     * Gets installed languages in the system
     *
     * @return array
     */
    public static function getLngList(): array
    {
        $lng_list = [];
        foreach (glob(ROOT_PATH . 'system/locale/*.ini') as $val) {
            $iso = pathinfo($val, PATHINFO_FILENAME);
            $lang_data = parse_ini_file($val);
            $lng_list[$iso] = [
                'name'    => ! empty($lang_data['name']) ? $lang_data['name'] : $iso,
                'version' => ! empty($lang_data['version']) ? (float) $lang_data['version'] : 1,
            ];
        }

        return $lng_list;
    }

    /**
     * Updates the list of languages.
     */
    public static function updateList(): void
    {
        $config = di('config')['johncms'];
        $config['lng_list'] = self::getLngList();
        $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $config], true) . ";\n";
        if (! file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
            echo 'ERROR: Can not write system.local.php';
            exit;
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Deletes language files.
     *
     * @param $language
     */
    public static function remove($language): void
    {
        $config = di('config')['johncms'];
        if (array_key_exists($language, $config['lng_list'])) {
            $arr_files = glob(ROOT_PATH . 'modules/*/locale/' . $language . '.lng.php');
            $arr_files[] = ROOT_PATH . 'system/locale/' . $language . '.ini';
            $arr_files[] = ROOT_PATH . 'system/locale/' . $language . '.lng.php';
            $arr_files[] = ROOT_PATH . 'themes/default/assets/images/flags/' . $language . '.png';
            foreach ($arr_files as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Getting a list of available languages
     *
     * @return array
     */
    public static function getAvailableLanguages(): array
    {
        $updates_url = 'https://johncms.com/updates/languages/?cms_version=' . CMS_VERSION;
        $all_languages = [];
        $updates = file_get_contents($updates_url);
        if (! empty($updates)) {
            $all_languages = json_decode($updates, true);
        }

        return $all_languages;
    }

    /**
     * Installs the language into the system
     *
     * @param $language
     */
    public static function install($language): void
    {
        $all_languages = self::getAvailableLanguages();
        if (array_key_exists($language, $all_languages)) {
            $lang = $all_languages[$language];
            if (! empty($lang['path'])) {
                $filename = basename($lang['path']);
                $tmp_file_path = DATA_PATH . 'tmp/' . $filename;
                if (copy('https://johncms.com' . $lang['path'], $tmp_file_path)) {
                    // Open archive
                    $zip = new ZipArchive();
                    if ($zip->open($tmp_file_path) === true) {
                        $zip->extractTo(ROOT_PATH);
                        $zip->close();
                    }
                    unlink($tmp_file_path);
                }
            }
        }
    }
}
