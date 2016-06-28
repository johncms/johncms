<?php

// Получаем список языков
$iso_list = [];
foreach (glob('incfiles/languages/*', GLOB_ONLYDIR) as $isofile) {
    $iso_list[] = basename($isofile);
}

// Конвертируем языки из массивов в формат .POT
$files_en = glob('incfiles/languages/en/*.lng');
foreach ($files_en as $file) {
    $filename = explode('.', basename($file))[0];
    $english_src = parse_ini_file($file);
    $russian_src = parse_ini_file('incfiles/languages/ru/' . $filename . '.lng');

    foreach ($iso_list as $iso) {
        $out = [
            'msgid ""',
            'msgstr ""' . "\n",
        ];

        if ($iso == 'en') {
            foreach ($english_src as $val) {
                $val = str_replace("'", '\"', $val);
                $val = str_replace('<br />', '<br>', $val);
                $val = str_replace('<br/>', '<br>', $val);

                $out[] = 'msgid "' . $val . '"';
                $out[] = 'msgstr ""' . "\n";
            }

            $ext = '.pot';
        } else {
            $target_src = parse_ini_file('incfiles/languages/' . $iso . '/' . $filename . '.lng');

            foreach ($english_src as $key => $val) {
                if (isset($target_src[$key]) && $target_src[$key] != $val) {
                    // Очищаем непереведенные Русские фразы
                    if ($iso != 'ru' && $iso != 'uk' && $target_src[$key] == $russian_src[$key]) {
                        continue;
                    }

                    $val = str_replace("'", '\"', $val);
                    $val = str_replace('<br />', '<br>', $val);
                    $val = str_replace('<br/>', '<br>', $val);

                    $target_src[$key] = str_replace("'", '\"', $target_src[$key]);
                    $target_src[$key] = str_replace('<br />', '<br>', $target_src[$key]);
                    $target_src[$key] = str_replace('<br/>', '<br>', $target_src[$key]);

                    $out[] = 'msgid "' . $val . '"';
                    $out[] = 'msgstr "' . $target_src[$key] . '"' . "\n";
                }
            }

            $ext = '.po';
        }

        file_put_contents('system/locale/' . $iso . '/' . ($filename == '_core' ? 'default' : $filename) . $ext, implode("\n", $out));
    }
}
