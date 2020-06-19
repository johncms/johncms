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

class Languages
{
    /**
     * Updates the list of languages.
     */
    public static function updateList(): void
    {
        $config = di('config')['johncms'];
        $lng_list = [];
        foreach (glob(ROOT_PATH . 'system/locale/*.ini') as $val) {
            $iso = pathinfo($val, PATHINFO_FILENAME);
            $lang_data = parse_ini_file($val);
            $lng_list[$iso] = [
                'name'    => ! empty($lang_data['name']) ? $lang_data['name'] : $iso,
                'version' => ! empty($lang_data['version']) ? (float) $lang_data['version'] : 1,
            ];
        }
        $config['lng_list'] = $lng_list;
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
}
