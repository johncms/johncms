<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
// +------------------------------------------------------------------------+
// | Copyright (c) Colin Verot 2003-2009. All rights reserved.              |
// | Version       0.28                                                     |
// +------------------------------------------------------------------------+
*/

defined('_IN_JOHNCMS') or die('Restricted access');

class upload {
    var $version;
    var $file_src_name;
    var $file_src_name_body;
    var $file_src_name_ext;
    var $file_src_mime;
    var $file_src_size;
    var $file_src_error;
    var $file_src_pathname;
    var $file_src_temp;
    var $file_dst_path;
    var $file_dst_name;
    var $file_dst_name_body;
    var $file_dst_name_ext;
    var $file_dst_pathname;
    var $image_src_x;
    var $image_src_y;
    var $image_src_bits;
    var $image_src_pixels;
    var $image_src_type;
    var $image_dst_x;
    var $image_dst_y;
    var $image_supported;
    var $file_is_image;
    var $uploaded;
    var $no_upload_check;
    var $processed;
    var $error;
    var $log;
    var $file_new_name_body;
    var $file_name_body_add;
    var $file_name_body_pre;
    var $file_new_name_ext;
    var $file_safe_name;
    var $mime_check;
    var $mime_fileinfo;
    var $mime_file;
    var $mime_magic;
    var $mime_getimagesize;
    var $no_script;
    var $file_auto_rename;
    var $dir_auto_create;
    var $dir_auto_chmod;
    var $dir_chmod;
    var $file_overwrite;
    var $file_max_size;
    var $image_resize;
    var $image_convert;
    var $image_x;
    var $image_y;
    var $image_ratio;
    var $image_ratio_crop;
    var $image_ratio_fill;
    var $image_ratio_pixels;
    var $image_ratio_no_zoom_in;
    var $image_ratio_no_zoom_out;
    var $image_ratio_x;
    var $image_ratio_y;
    var $image_max_width;
    var $image_max_height;
    var $image_max_pixels;
    var $image_max_ratio;
    var $image_min_width;
    var $image_min_height;
    var $image_min_pixels;
    var $image_min_ratio;
    var $jpeg_quality;
    var $jpeg_size;
    var $preserve_transparency;
    var $image_is_transparent;
    var $image_transparent_color;
    var $image_background_color;
    var $image_default_color;
    var $image_is_palette;
    var $image_brightness;
    var $image_contrast;
    var $image_threshold;
    var $image_tint_color;
    var $image_overlay_color;
    var $image_overlay_percent;
    var $image_negative;
    var $image_greyscale;
    var $image_text;
    var $image_text_direction;
    var $image_text_color;
    var $image_text_percent;
    var $image_text_background;
    var $image_text_background_percent;
    var $image_text_font;
    var $image_text_position;
    var $image_text_x;
    var $image_text_y;
    var $image_text_padding;
    var $image_text_padding_x;
    var $image_text_padding_y;
    var $image_text_alignment;
    var $image_text_line_spacing;
    var $image_reflection_height;
    var $image_reflection_space;
    var $image_reflection_color;
    var $image_reflection_opacity;
    var $image_flip;
    var $image_rotate;
    var $image_crop;
    var $image_precrop;
    var $image_bevel;
    var $image_bevel_color1;
    var $image_bevel_color2;
    var $image_border;
    var $image_border_color;
    var $image_frame;
    var $image_frame_colors;
    var $image_watermark;
    var $image_watermark_position;
    var $image_watermark_x;
    var $image_watermark_y;
    var $allowed;
    var $forbidden;
    var $translation;
    var $language;

    function init() {
        $this->file_new_name_body       = '';
        $this->file_name_body_add       = '';
        $this->file_name_body_pre       = '';
        $this->file_new_name_ext        = '';
        $this->file_safe_name           = true;
        $this->file_overwrite           = false;
        $this->file_auto_rename         = true;
        $this->dir_auto_create          = true;
        $this->dir_auto_chmod           = true;
        $this->dir_chmod                = 0777;
        $this->mime_check               = true;
        $this->mime_fileinfo            = true;
        $this->mime_file                = true;
        $this->mime_magic               = true;
        $this->mime_getimagesize        = true;
        $this->no_script                = true;
        $val = trim(ini_get('upload_max_filesize'));
        $last = strtolower($val{strlen($val)-1});
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        $this->file_max_size = $val;
        $this->image_resize             = false;
        $this->image_convert            = '';
        $this->image_x                  = 150;
        $this->image_y                  = 150;
        $this->image_ratio              = false;
        $this->image_ratio_crop         = false;
        $this->image_ratio_fill         = false;
        $this->image_ratio_pixels       = false;
        $this->image_ratio_no_zoom_in   = false;
        $this->image_ratio_no_zoom_out  = false;
        $this->image_ratio_x            = false;
        $this->image_ratio_y            = false;
        $this->jpeg_quality             = 85;
        $this->jpeg_size                = null;
        $this->preserve_transparency    = false;
        $this->image_is_transparent     = false;
        $this->image_transparent_color  = null;
        $this->image_background_color   = null;
        $this->image_default_color      = '#ffffff';
        $this->image_is_palette         = false;

        $this->image_max_width          = null;
        $this->image_max_height         = null;
        $this->image_max_pixels         = null;
        $this->image_max_ratio          = null;
        $this->image_min_width          = null;
        $this->image_min_height         = null;
        $this->image_min_pixels         = null;
        $this->image_min_ratio          = null;

        $this->image_brightness         = null;
        $this->image_contrast           = null;
        $this->image_threshold          = null;
        $this->image_tint_color         = null;
        $this->image_overlay_color      = null;
        $this->image_overlay_percent    = null;
        $this->image_negative           = false;
        $this->image_greyscale          = false;

        $this->image_text               = null;
        $this->image_text_direction     = null;
        $this->image_text_color         = '#FFFFFF';
        $this->image_text_percent       = 100;
        $this->image_text_background    = null;
        $this->image_text_background_percent = 100;
        $this->image_text_font          = 5;
        $this->image_text_x             = null;
        $this->image_text_y             = null;
        $this->image_text_position      = null;
        $this->image_text_padding       = 0;
        $this->image_text_padding_x     = null;
        $this->image_text_padding_y     = null;
        $this->image_text_alignment     = 'C';
        $this->image_text_line_spacing  = 0;

        $this->image_reflection_height  = null;
        $this->image_reflection_space   = 2;
        $this->image_reflection_color   = '#ffffff';
        $this->image_reflection_opacity = 60;

        $this->image_watermark          = null;
        $this->image_watermark_x        = null;
        $this->image_watermark_y        = null;
        $this->image_watermark_position = null;

        $this->image_flip               = null;
        $this->image_rotate             = null;
        $this->image_crop               = null;
        $this->image_precrop            = null;

        $this->image_bevel              = null;
        $this->image_bevel_color1       = '#FFFFFF';
        $this->image_bevel_color2       = '#000000';
        $this->image_border             = null;
        $this->image_border_color       = '#FFFFFF';
        $this->image_frame              = null;
        $this->image_frame_colors       = '#FFFFFF #999999 #666666 #000000';

        $this->forbidden = array();
        $this->allowed = array("application/rar",
                               "application/x-rar-compressed",
                               "application/arj",
                               "application/excel",
                               "application/gnutar",
                               "application/octet-stream",
                               "application/pdf",
                               "application/powerpoint",
                               "application/postscript",
                               "application/plain",
                               "application/rtf",
                               "application/vocaltec-media-file",
                               "application/wordperfect",
                               "application/x-bzip",
                               "application/x-bzip2",
                               "application/x-compressed",
                               "application/x-excel",
                               "application/x-gzip",
                               "application/x-latex",
                               "application/x-midi",
                               "application/x-msexcel",
                               "application/x-rtf",
                               "application/x-sit",
                               "application/x-stuffit",
                               "application/x-shockwave-flash",
                               "application/x-troff-msvideo",
                               "application/x-zip-compressed",
                               "application/xml",
                               "application/zip",
                               "application/msword",
                               "application/mspowerpoint",
                               "application/vnd.ms-excel",
                               "application/vnd.ms-powerpoint",
                               "application/vnd.ms-word",
                               "application/vnd.ms-word.document.macroEnabled.12",
                               "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                               "application/vnd.ms-word.template.macroEnabled.12",
                               "application/vnd.openxmlformats-officedocument.wordprocessingml.template",
                               "application/vnd.ms-powerpoint.template.macroEnabled.12",
                               "application/vnd.openxmlformats-officedocument.presentationml.template",
                               "application/vnd.ms-powerpoint.addin.macroEnabled.12",
                               "application/vnd.ms-powerpoint.slideshow.macroEnabled.12",
                               "application/vnd.openxmlformats-officedocument.presentationml.slideshow",
                               "application/vnd.ms-powerpoint.presentation.macroEnabled.12",
                               "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                               "application/vnd.ms-excel.addin.macroEnabled.12",
                               "application/vnd.ms-excel.sheet.binary.macroEnabled.12",
                               "application/vnd.ms-excel.sheet.macroEnabled.12",
                               "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                               "application/vnd.ms-excel.template.macroEnabled.12",
                               "application/vnd.openxmlformats-officedocument.spreadsheetml.template",
                               "audio/*",
                               "image/*",
                               "video/*",
                               "multipart/x-zip",
                               "multipart/x-gzip",
                               "text/richtext",
                               "text/plain",
                               "text/xml");

    }

    function upload($file, $lang = 'en_GB') {
        $this->version            = '0.28';
        $this->file_src_name      = '';
        $this->file_src_name_body = '';
        $this->file_src_name_ext  = '';
        $this->file_src_mime      = '';
        $this->file_src_size      = '';
        $this->file_src_error     = '';
        $this->file_src_pathname  = '';
        $this->file_src_temp      = '';
        $this->file_dst_path      = '';
        $this->file_dst_name      = '';
        $this->file_dst_name_body = '';
        $this->file_dst_name_ext  = '';
        $this->file_dst_pathname  = '';
        $this->image_src_x        = null;
        $this->image_src_y        = null;
        $this->image_src_bits     = null;
        $this->image_src_type     = null;
        $this->image_src_pixels   = null;
        $this->image_dst_x        = 0;
        $this->image_dst_y        = 0;
        $this->uploaded           = true;
        $this->no_upload_check    = false;
        $this->processed          = true;
        $this->error              = '';
        $this->log                = '';
        $this->allowed            = array();
        $this->forbidden          = array();
        $this->file_is_image      = false;
        $this->init();
        $info                     = null;
        $mime_from_browser        = null;
        $this->translation        = array();
        $this->translation['file_error']                  = 'Ошибка файлв. Попробуйте еще раз.';
        $this->translation['local_file_missing']          = 'Локальный файл не существует.';
        $this->translation['local_file_not_readable']     = 'Локальный файл закрыт для чтения.';
        $this->translation['uploaded_too_big_ini']        = 'Ошибка загрузки файла (загруженный файл превышает лимит директивы the upload_max_filesize из php.ini).';
        $this->translation['uploaded_too_big_html']       = 'Ошибка загрузки файла (загруженный файл превышает лимит директивы MAX_FILE_SIZE определенной в HTML-форме).';
        $this->translation['uploaded_partial']            = 'Ошибка загрузки файла (файл загружен частично).';
        $this->translation['uploaded_missing']            = 'Ошибка загрузки файла (файл не был загружен).';
        $this->translation['uploaded_no_tmp_dir']         = 'File upload error (missing a temporary folder).';
        $this->translation['uploaded_cant_write']         = 'File upload error (failed to write file to disk).';
        $this->translation['uploaded_err_extension']      = 'File upload error (file upload stopped by extension).';
        $this->translation['uploaded_unknown']            = 'Ошибка загрузки файла (неизвестный код ошибки).';
        $this->translation['try_again']                   = 'Ошибка загрузки файла. Попробуйте еще раз.';
        $this->translation['file_too_big']                = 'Файл очень большой.';
        $this->translation['no_mime']                     = 'Невозможно определить MIME-тип файла.';
        $this->translation['incorrect_file']              = 'Некорректный тип файла.';
        $this->translation['image_too_wide']              = 'Изображение очень широкое.';
        $this->translation['image_too_narrow']            = 'Изображение очень узкое.';
        $this->translation['image_too_high']              = 'Изображение очень высокое.';
        $this->translation['image_too_short']             = 'Изображение очень короткое.';
        $this->translation['ratio_too_high']              = 'Соотношение сторон очень велико (изображение очень широкое).';
        $this->translation['ratio_too_low']               = 'Соотношение сторон очень мало (изображение очень высокое).';
        $this->translation['too_many_pixels']             = 'В изображении очень много пикселей.';
        $this->translation['not_enough_pixels']           = 'В изображении недостаточно пикселей.';
        $this->translation['file_not_uploaded']           = 'Файл не загружен. Невозможно продолжить процесс.';
        $this->translation['already_exists']              = '%s существует. Измените имя файла.';
        $this->translation['temp_file_missing']           = 'Некорректный временый файл. Невозможно продолжить процесс.';
        $this->translation['source_missing']              = 'Некорректный загруженный файл. Невозможно продолжить процесс.';
        $this->translation['destination_dir']             = 'Директория назначения не может быть создана. Невозможно продолжить процесс.';
        $this->translation['destination_dir_missing']     = 'Директория назначения не существует. Невозможно продолжить процесс.';
        $this->translation['destination_path_not_dir']    = 'Путь назначения не является директорией. Невозможно продолжить процесс.';
        $this->translation['destination_dir_write']       = 'Директория назначения закрыта для записи. Невозможно продолжить процесс.';
        $this->translation['destination_path_write']      = 'Путь назначения закрыт для записи. Невозможно продолжить процесс.';
        $this->translation['temp_file']                   = 'Невозможно создать временный файл. Невозможно продолжить процесс.';
        $this->translation['source_not_readable']         = 'Исходный файл нечитабельный. Невозможно продолжить процесс.';
        $this->translation['no_create_support']           = 'Создание из %s не поддерживается.';
        $this->translation['create_error']                = 'Ошибка создания %s изображения из оригинала.';
        $this->translation['source_invalid']              = 'Невозможно прочитать исходный файл.';
        $this->translation['gd_missing']                  = 'Библиотека GD не обнаружена.';
        $this->translation['watermark_no_create_support'] = '%s не поддерживается, невозможно прочесть водный знак.';
        $this->translation['watermark_create_error']      = '%s не поддерживается чтение, невозможно создать водный знак.';
        $this->translation['watermark_invalid']           = 'Неизвестный формат изображения, невозможно прочесть водный знак.';
        $this->translation['file_create']                 = '%s не поддерживается.';
        $this->translation['no_conversion_type']          = 'Тип конверсии не указан.';
        $this->translation['copy_failed']                 = 'Ошибка копирования файла на сервер. Команда copy() выполнена с ошибкой.';
        $this->translation['reading_failed']              = 'Ошибка чтения файла.';
        $this->lang               = $lang;
        if ($this->lang != 'en_GB' && file_exists(dirname(__FILE__).'/lang/class.upload.' . $lang . '.php')) {
            $translation = null;
            include(dirname(__FILE__).'/lang/class.upload.' . $lang . '.php');
            if (is_array($translation)) {
                $this->translation = array_merge($this->translation, $translation);
            } else {
                $this->lang = 'en_GB';
            }
        }
        $this->image_supported = array();
        if ($this->gdversion()) {
            if (imagetypes() & IMG_GIF) {
                $this->image_supported['image/gif'] = 'gif';
            }
            if (imagetypes() & IMG_JPG) {
                $this->image_supported['image/jpg'] = 'jpg';
                $this->image_supported['image/jpeg'] = 'jpg';
                $this->image_supported['image/pjpeg'] = 'jpg';
            }
            if (imagetypes() & IMG_PNG) {
                $this->image_supported['image/png'] = 'png';
                $this->image_supported['image/x-png'] = 'png';
            }
            if (imagetypes() & IMG_WBMP) {
                $this->image_supported['image/bmp'] = 'bmp';
                $this->image_supported['image/x-ms-bmp'] = 'bmp';
                $this->image_supported['image/x-windows-bmp'] = 'bmp';
            }
        }
        if (empty($this->log)) {
            $this->log .= '<b>system information</b><br />';
            $inis = ini_get_all();
            $open_basedir = (array_key_exists('open_basedir', $inis) && array_key_exists('local_value', $inis['open_basedir']) && !empty($inis['open_basedir']['local_value'])) ? $inis['open_basedir']['local_value'] : false;
            $gd           = $this->gdversion() ? $this->gdversion(true) : 'GD not present';
            $supported    = trim((in_array('png', $this->image_supported) ? 'png' : '') . ' ' . (in_array('jpg', $this->image_supported) ? 'jpg' : '') . ' ' . (in_array('gif', $this->image_supported) ? 'gif' : '') . ' ' . (in_array('bmp', $this->image_supported) ? 'bmp' : ''));
            $this->log .= '-&nbsp;class version           : ' . $this->version . '<br />';
            $this->log .= '-&nbsp;GD version              : ' . $gd . '<br />';
            $this->log .= '-&nbsp;supported image types   : ' . (!empty($supported) ? $supported : 'none') . '<br />';
            $this->log .= '-&nbsp;open_basedir            : ' . (!empty($open_basedir) ? $open_basedir : 'no restriction') . '<br />';
            $this->log .= '-&nbsp;language                : ' . $this->lang . '<br />';
        }
        if (!$file) {
            $this->uploaded = false;
            $this->error = $this->translate('file_error');
        }
        if (!is_array($file)) {
            if (empty($file)) {
                $this->uploaded = false;
                $this->error = $this->translate('file_error');
            } else {
                $this->no_upload_check = TRUE;
                $this->log .= '<b>' . $this->translate("source is a local file") . ' ' . $file . '</b><br />';
                if ($this->uploaded && !file_exists($file)) {
                    $this->uploaded = false;
                    $this->error = $this->translate('local_file_missing');
                }
                if ($this->uploaded && !is_readable($file)) {
                    $this->uploaded = false;
                    $this->error = $this->translate('local_file_not_readable');
                }
                if ($this->uploaded) {
                    $this->file_src_pathname   = $file;
                    $this->file_src_name       = basename($file);
                    $this->log .= '- local file name OK<br />';
                    preg_match('/\.([^\.]*$)/', $this->file_src_name, $extension);
                    if (is_array($extension)) {
                        $this->file_src_name_ext      = strtolower($extension[1]);
                        $this->file_src_name_body     = substr($this->file_src_name, 0, ((strlen($this->file_src_name) - strlen($this->file_src_name_ext)))-1);
                    } else {
                        $this->file_src_name_ext      = '';
                        $this->file_src_name_body     = $this->file_src_name;
                    }
                    $this->file_src_size = (file_exists($file) ? filesize($file) : 0);
                }
                $this->file_src_error = 0;
            }
        } else {
            $this->log .= '<b>source is an uploaded file</b><br />';
            if ($this->uploaded) {
                $this->file_src_error         = trim($file['error']);
                switch($this->file_src_error) {
                    case UPLOAD_ERR_OK:
                        $this->log .= '- upload OK<br />';
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                        $this->uploaded = false;
                        $this->error = $this->translate('uploaded_too_big_ini');
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->uploaded = false;
                        $this->error = $this->translate('uploaded_too_big_html');
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->uploaded = false;
                        $this->error = $this->translate('uploaded_partial');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $this->uploaded = false;
                        $this->error = $this->translate('uploaded_missing');
                        break;
                    case @UPLOAD_ERR_NO_TMP_DIR:
                        $this->uploaded = false;
                        $this->error = $this->translate('uploaded_no_tmp_dir');
                        break;
                    case @UPLOAD_ERR_CANT_WRITE:
                        $this->uploaded = false;
                        $this->error = $this->translate('uploaded_cant_write');
                        break;
                    case @UPLOAD_ERR_EXTENSION:
                        $this->uploaded = false;
                        $this->error = $this->translate('uploaded_err_extension');
                        break;
                    default:
                        $this->uploaded = false;
                        $this->error = $this->translate('uploaded_unknown') . ' ('.$this->file_src_error.')';
                }
            }
            if ($this->uploaded) {
                $this->file_src_pathname   = $file['tmp_name'];
                $this->file_src_name       = $file['name'];
                if ($this->file_src_name == '') {
                    $this->uploaded = false;
                    $this->error = $this->translate('try_again');
                }
            }
            if ($this->uploaded) {
                $this->log .= '- file name OK<br />';
                preg_match('/\.([^\.]*$)/', $this->file_src_name, $extension);
                if (is_array($extension)) {
                    $this->file_src_name_ext      = strtolower($extension[1]);
                    $this->file_src_name_body     = substr($this->file_src_name, 0, ((strlen($this->file_src_name) - strlen($this->file_src_name_ext)))-1);
                } else {
                    $this->file_src_name_ext      = '';
                    $this->file_src_name_body     = $this->file_src_name;
                }
                $this->file_src_size = $file['size'];
                $mime_from_browser = $file['type'];
            }
        }
        if ($this->uploaded) {
            $this->log .= '<b>determining MIME type</b><br />';
            $this->file_src_mime = null;
            if (!$this->file_src_mime || !is_string($this->file_src_mime) || empty($this->file_src_mime) || strpos($this->file_src_mime, '/') === FALSE) {
                if ($this->mime_fileinfo) {
                    $this->log .= '- Checking MIME type with Fileinfo PECL extension<br />';
                    if (function_exists('finfo_open')) {
                        if ($this->mime_fileinfo !== '') {
                            if ($this->mime_fileinfo === true) {
                                if (getenv('MAGIC') === FALSE) {
                                    if (substr(PHP_OS, 0, 3) == 'WIN') {
                                        $path = realpath(ini_get('extension_dir') . '/../') . 'extras/magic';
                                    } else {
                                        $path = '/usr/share/file/magic';
                                    }
                                    $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MAGIC path defaults to ' . $path . '<br />';
                                } else {
                                    $path = getenv('MAGIC');
                                    $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MAGIC path is set to ' . $path . ' from MAGIC variable<br />';
                                }
                            } else {
                                $path = $this->mime_fileinfo;
                                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MAGIC path is set to ' . $path . '<br />';
                            }
                            $f = @finfo_open(FILEINFO_MIME, $path);
                        } else {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MAGIC path will not be used<br />';
                            $f = @finfo_open(FILEINFO_MIME);
                        }
                        if (is_resource($f)) {
                            $mime = finfo_file($f, realpath($this->file_src_pathname));
                            finfo_close($f);
                            $this->file_src_mime = $mime;
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MIME type detected as ' . $this->file_src_mime . ' by Fileinfo PECL extension<br />';
                        } else {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;Fileinfo PECL extension failed (finfo_open)<br />';
                        }
                    } elseif (class_exists('finfo')) {
                        $f = new finfo( FILEINFO_MIME );
                        if ($f) {
                            $this->file_src_mime = $f->file(realpath($this->file_src_pathname));
                            $this->log .= '- MIME type detected as ' . $this->file_src_mime . ' by Fileinfo PECL extension<br />';
                        } else {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;Fileinfo PECL extension failed (finfo)<br />';
                        }
                    } else {
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;Fileinfo PECL extension not available<br />';
                    }
                } else {
                    $this->log .= '- Fileinfo PECL extension deactivated<br />';
                }
            }
            if (!$this->file_src_mime || !is_string($this->file_src_mime) || empty($this->file_src_mime) || strpos($this->file_src_mime, '/') === FALSE) {
                if ($this->mime_file) {
                    $this->log .= '- Checking MIME type with UNIX file() command<br />';
                    if (substr(PHP_OS, 0, 3) != 'WIN') {
                        if (strlen($mime = @exec("file -bi ".escapeshellarg($this->file_src_pathname))) != 0) {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MIME returned as ' . $mime . '<br />';
                            if (strpos($mime, ';') !== FALSE) {
                                $temp = split(';', $mime);
                                $this->file_src_mime = $temp[0];
                            } else if (strpos($mime, ' ') !== FALSE) {
                                $temp = split(' ', $mime);
                                $this->file_src_mime = $temp[0];
                            } else {
                                $this->file_src_mime = trim($mime);
                            }
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MIME type detected as ' . $this->file_src_mime . ' by UNIX file() command<br />';
                        } else {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;UNIX file() command failed<br />';
                        }
                    } else {
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;UNIX file() command not availabled<br />';
                    }
                } else {
                    $this->log .= '- UNIX file() command is deactivated<br />';
                }
            }
            if (!$this->file_src_mime || !is_string($this->file_src_mime) || empty($this->file_src_mime) || strpos($this->file_src_mime, '/') === FALSE) {
                if ($this->mime_magic) {
                    $this->log .= '- Checking MIME type with mime.magic file (mime_content_type())<br />';
                    if (function_exists('mime_content_type')) {
                        $this->file_src_mime = mime_content_type($this->file_src_pathname);
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MIME type detected as ' . $this->file_src_mime . ' by mime_content_type()<br />';
                    } else {
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;mime_content_type() is not available<br />';
                    }
                } else {
                    $this->log .= '- mime.magic file (mime_content_type()) is deactivated<br />';
                }
            }
            if (!$this->file_src_mime || !is_string($this->file_src_mime) || empty($this->file_src_mime) || strpos($this->file_src_mime, '/') === FALSE) {
                if ($this->mime_getimagesize) {
                    $this->log .= '- Checking MIME type with getimagesize()<br />';
                    $info = getimagesize($this->file_src_pathname);
                    if (is_array($info) && array_key_exists('mime', $info)) {
                        $this->file_src_mime = trim($info['mime']);
                        if (empty($this->file_src_mime)) {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MIME empty, guessing from type<br />';
                            $mime = (is_array($info) && array_key_exists(2, $info) ? $info[2] : null); // 1 = GIF, 2 = JPG, 3 = PNG
                            $this->file_src_mime = ($mime==IMAGETYPE_GIF ? 'image/gif' : ($mime==IMAGETYPE_JPEG ? 'image/jpeg' : ($mime==IMAGETYPE_PNG ? 'image/png' : ($mime==IMAGETYPE_BMP ? 'image/bmp' : null))));
                        }
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;MIME type detected as ' . $this->file_src_mime . ' by PHP getimagesize() function<br />';
                    } else {
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;getimagesize() failed<br />';
                    }
                } else {
                    $this->log .= '- getimagesize() is deactivated<br />';
                }
            }
            if (!empty($mime_from_browser) && !$this->file_src_mime || !is_string($this->file_src_mime) || empty($this->file_src_mime)) {
                $this->file_src_mime =$mime_from_browser;
                $this->log .= '- MIME type detected as ' . $this->file_src_mime . ' by browser<br />';
            }
            if ($this->file_src_mime == 'application/octet-stream' || !$this->file_src_mime || !is_string($this->file_src_mime) || empty($this->file_src_mime) || strpos($this->file_src_mime, '/') === FALSE) {
                if ($this->file_src_mime == 'application/octet-stream') $this->log .= '- Flash may be rewriting MIME as application/octet-stream<br />';
                $this->log .= '- Try to guess MIME type from file extension (' . $this->file_src_name_ext . '): ';
                switch($this->file_src_name_ext) {
                    case 'jpg':
                    case 'jpeg':
                    case 'jpe':
                        $this->file_src_mime = 'image/jpeg';
                        break;
                    case 'gif':
                        $this->file_src_mime = 'image/gif';
                        break;
                    case 'png':
                        $this->file_src_mime = 'image/png';
                        break;
                    case 'bmp':
                        $this->file_src_mime = 'image/bmp';
                        break;
                    case 'flv':
                        $this->file_src_mime = 'video/x-flv';
                        break;
                    case 'js' :
                        $this->file_src_mime = 'application/x-javascript';
                        break;
                    case 'json' :
                        $this->file_src_mime = 'application/json';
                        break;
                    case 'tiff' :
                        $this->file_src_mime = 'image/tiff';
                        break;
                    case 'css' :
                        $this->file_src_mime = 'text/css';
                        break;
                    case 'xml' :
                        $this->file_src_mime = 'application/xml';
                        break;
                    case 'doc' :
                    case 'docx' :
                        $this->file_src_mime = 'application/msword';
                        break;
                    case 'xls' :
                    case 'xlt' :
                    case 'xlm' :
                    case 'xld' :
                    case 'xla' :
                    case 'xlc' :
                    case 'xlw' :
                    case 'xll' :
                        $this->file_src_mime = 'application/vnd.ms-excel';
                        break;
                    case 'ppt' :
                    case 'pps' :
                        $this->file_src_mime = 'application/vnd.ms-powerpoint';
                        break;
                    case 'rtf' :
                        $this->file_src_mime = 'application/rtf';
                        break;
                    case 'pdf' :
                        $this->file_src_mime = 'application/pdf';
                        break;
                    case 'html' :
                    case 'htm' :
                    case 'php' :
                        $this->file_src_mime = 'text/html';
                        break;
                    case 'txt' :
                        $this->file_src_mime = 'text/plain';
                        break;
                    case 'mpeg' :
                    case 'mpg' :
                    case 'mpe' :
                        $this->file_src_mime = 'video/mpeg';
                        break;
                    case 'mp3' :
                        $this->file_src_mime = 'audio/mpeg3';
                        break;
                    case 'wav' :
                        $this->file_src_mime = 'audio/wav';
                        break;
                    case 'aiff' :
                    case 'aif' :
                        $this->file_src_mime = 'audio/aiff';
                        break;
                    case 'avi' :
                        $this->file_src_mime = 'video/msvideo';
                        break;
                    case 'wmv' :
                        $this->file_src_mime = 'video/x-ms-wmv';
                        break;
                    case 'mov' :
                        $this->file_src_mime = 'video/quicktime';
                        break;
                    case 'zip' :
                        $this->file_src_mime = 'application/zip';
                        break;
                    case 'tar' :
                        $this->file_src_mime = 'application/x-tar';
                        break;
                    case 'swf' :
                        $this->file_src_mime = 'application/x-shockwave-flash';
                        break;
                }
                if ($this->file_src_mime == 'application/octet-stream') {
                    $this->log .= 'doesn\'t look like anything known<br />';
                } else {
                    $this->log .= 'MIME type set to ' . $this->file_src_mime . '<br />';
                }
            }
            if (!$this->file_src_mime || !is_string($this->file_src_mime) || empty($this->file_src_mime) || strpos($this->file_src_mime, '/') === FALSE) {
                $this->log .= '- MIME type couldn\'t be detected! (' . (string) $this->file_src_mime . ')<br />';
            }
            if ($this->file_src_mime && is_string($this->file_src_mime) && !empty($this->file_src_mime) && array_key_exists($this->file_src_mime, $this->image_supported)) {
                $this->file_is_image = true;
                $this->image_src_type = $this->image_supported[$this->file_src_mime];
            }
            if ($this->file_is_image) {
                $info = @getimagesize($this->file_src_pathname);
                if (is_array($info)) {
                    $this->image_src_x    = $info[0];
                    $this->image_src_y    = $info[1];
                    $this->image_src_pixels = $this->image_src_x * $this->image_src_y;
                    $this->image_src_bits = array_key_exists('bits', $info) ? $info['bits'] : null;
                } else {
                    $this->log .= '- can\'t retrieve image information. open_basedir restriction in place?<br />';
                }
            }
            $this->log .= '<b>source variables</b><br />';
            $this->log .= '- You can use all these before calling process()<br />';
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_src_name         : ' . $this->file_src_name . '<br />';
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_src_name_body    : ' . $this->file_src_name_body . '<br />';
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_src_name_ext     : ' . $this->file_src_name_ext . '<br />';
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_src_pathname     : ' . $this->file_src_pathname . '<br />';
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_src_mime         : ' . $this->file_src_mime . '<br />';
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_src_size         : ' . $this->file_src_size . ' (max= ' . $this->file_max_size . ')<br />';
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_src_error        : ' . $this->file_src_error . '<br />';
            if ($this->file_is_image) {
                $this->log .= '- source file is an image<br />';
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;image_src_x           : ' . $this->image_src_x . '<br />';
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;image_src_y           : ' . $this->image_src_y . '<br />';
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;image_src_pixels      : ' . $this->image_src_pixels . '<br />';
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;image_src_type        : ' . $this->image_src_type . '<br />';
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;image_src_bits        : ' . $this->image_src_bits . '<br />';
            }
        }
    }

    function gdversion($full = false) {
        static $gd_version = null;
        static $gd_full_version = null;
        if ($gd_version === null) {
            if (function_exists('gd_info')) {
                $gd = gd_info();
                $gd = $gd["GD Version"];
                $regex = "/([\d\.]+)/i";
            } else {
                ob_start();
                phpinfo(8);
                $gd = ob_get_contents();
                ob_end_clean();
                $regex = "/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i";
            }
            if (preg_match($regex, $gd, $m)) {
                $gd_full_version = (string) $m[1];
                $gd_version = (float) $m[1];
            } else {
                $gd_full_version = 'none';
                $gd_version = 0;
            }
        }
        if ($full) {
            return $gd_full_version;
        } else {
            return $gd_version;
        }
    }

    function rmkdir($path, $mode = 0777) {
        return is_dir($path) || ( $this->rmkdir(dirname($path), $mode) && $this->_mkdir($path, $mode) );
    }

    function _mkdir($path, $mode = 0777) {
        $old = umask(0);
        $res = @mkdir($path, $mode);
        umask($old);
        return $res;
    }

    function translate($str, $tokens = array()) {
        if (array_key_exists($str, $this->translation)) $str = $this->translation[$str];
        if (is_array($tokens) && sizeof($tokens) > 0)   $str = vsprintf($str, $tokens);
        return $str;
    }

    function getcolors($color) {
        $r = sscanf($color, "#%2x%2x%2x");
        $red   = (array_key_exists(0, $r) && is_numeric($r[0]) ? $r[0] : 0);
        $green = (array_key_exists(1, $r) && is_numeric($r[1]) ? $r[1] : 0);
        $blue  = (array_key_exists(2, $r) && is_numeric($r[2]) ? $r[2] : 0);
        return array($red, $green, $blue);
    }

    function imagecreatenew($x, $y, $fill = true, $trsp = false) {
        if ($x < 1) $x = 1; if ($y < 1) $y = 1;
        if ($this->gdversion() >= 2 && !$this->image_is_palette) {
            $dst_im = imagecreatetruecolor($x, $y);
            if (empty($this->image_background_color) || $trsp) {
                imagealphablending($dst_im, false );
                imagefilledrectangle($dst_im, 0, 0, $x, $y, imagecolorallocatealpha($dst_im, 0, 0, 0, 127));
            }
        } else {
            $dst_im = imagecreate($x, $y);
            if (($fill && $this->image_is_transparent && empty($this->image_background_color)) || $trsp) {
                imagefilledrectangle($dst_im, 0, 0, $x, $y, $this->image_transparent_color);
                imagecolortransparent($dst_im, $this->image_transparent_color);
            }
        }
        if ($fill && !empty($this->image_background_color) && !$trsp) {
            list($red, $green, $blue) = $this->getcolors($this->image_background_color);
            $background_color = imagecolorallocate($dst_im, $red, $green, $blue);
            imagefilledrectangle($dst_im, 0, 0, $x, $y, $background_color);
        }
        return $dst_im;
    }

    function imagetransfer($src_im, $dst_im) {
        if (is_resource($dst_im)) imagedestroy($dst_im);
        $dst_im = & $src_im;
        return $dst_im;
    }

    function imagecopymergealpha(&$dst_im, &$src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct = 0) {
        $dst_x = (int) $dst_x;
        $dst_y = (int) $dst_y;
        $src_x = (int) $src_x;
        $src_y = (int) $src_y;
        $src_w = (int) $src_w;
        $src_h = (int) $src_h;
        $pct   = (int) $pct;
        $dst_w = imagesx($dst_im);
        $dst_h = imagesy($dst_im);
        for ($y = $src_y; $y < $src_h; $y++) {
            for ($x = $src_x; $x < $src_w; $x++) {
                if ($x + $dst_x >= 0 && $x + $dst_x < $dst_w && $x + $src_x >= 0 && $x + $src_x < $src_w
                 && $y + $dst_y >= 0 && $y + $dst_y < $dst_h && $y + $src_y >= 0 && $y + $src_y < $src_h) {
                    $dst_pixel = imagecolorsforindex($dst_im, imagecolorat($dst_im, $x + $dst_x, $y + $dst_y));
                    $src_pixel = imagecolorsforindex($src_im, imagecolorat($src_im, $x + $src_x, $y + $src_y));
                    $src_alpha = 1 - ($src_pixel['alpha'] / 127);
                    $dst_alpha = 1 - ($dst_pixel['alpha'] / 127);
                    $opacity = $src_alpha * $pct / 100;
                    if ($dst_alpha >= $opacity) $alpha = $dst_alpha;
                    if ($dst_alpha < $opacity)  $alpha = $opacity;
                    if ($alpha > 1) $alpha = 1;
                    if ($opacity > 0) {
                        $dst_red   = round(( ($dst_pixel['red']   * $dst_alpha * (1 - $opacity)) ) );
                        $dst_green = round(( ($dst_pixel['green'] * $dst_alpha * (1 - $opacity)) ) );
                        $dst_blue  = round(( ($dst_pixel['blue']  * $dst_alpha * (1 - $opacity)) ) );
                        $src_red   = round((($src_pixel['red']   * $opacity)) );
                        $src_green = round((($src_pixel['green'] * $opacity)) );
                        $src_blue  = round((($src_pixel['blue']  * $opacity)) );
                        $red   = round(($dst_red   + $src_red  ) / ($dst_alpha * (1 - $opacity) + $opacity));
                        $green = round(($dst_green + $src_green) / ($dst_alpha * (1 - $opacity) + $opacity));
                        $blue  = round(($dst_blue  + $src_blue ) / ($dst_alpha * (1 - $opacity) + $opacity));
                        if ($red   > 255) $red   = 255;
                        if ($green > 255) $green = 255;
                        if ($blue  > 255) $blue  = 255;
                        $alpha =  round((1 - $alpha) * 127);
                        $color = imagecolorallocatealpha($dst_im, $red, $green, $blue, $alpha);
                        imagesetpixel($dst_im, $x + $dst_x, $y + $dst_y, $color);
                    }
                }
            }
        }
        return true;
    }

    function process($server_path = null) {
        $this->error        = '';
        $this->processed    = true;
        $return_mode        = false;
        $return_content     = null;
        if (!$this->uploaded) {
            $this->error = $this->translate('file_not_uploaded');
            $this->processed = false;
        }
        if ($this->processed) {
            if (empty($server_path) || is_null($server_path)) {
                $this->log .= '<b>process file and return the content</b><br />';
                $return_mode = true;
            } else {
                if(strtolower(substr(PHP_OS, 0, 3)) === 'win') {
                    if (substr($server_path, -1, 1) != '\\') $server_path = $server_path . '\\';
                } else {
                    if (substr($server_path, -1, 1) != '/') $server_path = $server_path . '/';
                }
                $this->log .= '<b>process file to '  . $server_path . '</b><br />';
            }
        }
        if ($this->processed) {
            if ($this->file_src_size > $this->file_max_size ) {
                $this->processed = false;
                $this->error = $this->translate('file_too_big');
            } else {
                $this->log .= '- file size OK<br />';
            }
        }
        if ($this->processed) {
            if ($this->no_script) {
                if (((substr($this->file_src_mime, 0, 5) == 'text/' || strpos($this->file_src_mime, 'javascript') !== false)  && (substr($this->file_src_name, -4) != '.txt'))
                    || preg_match('/\.(php|pl|py|cgi|asp)$/i', $this->file_src_name) || empty($this->file_src_name_ext)) {
                    $this->file_src_mime = 'text/plain';
                    $this->log .= '- script '  . $this->file_src_name . ' renamed as ' . $this->file_src_name . '.txt!<br />';
                    $this->file_src_name_ext .= (empty($this->file_src_name_ext) ? 'txt' : '.txt');
                }
            }
            if ($this->mime_check && empty($this->file_src_mime)) {
                $this->processed = false;
                $this->error = $this->translate('no_mime');
            } else if ($this->mime_check && !empty($this->file_src_mime) && strpos($this->file_src_mime, '/') !== false) {
                list($m1, $m2) = explode('/', $this->file_src_mime);
                $allowed = false;
                foreach($this->allowed as $k => $v) {
                    list($v1, $v2) = explode('/', $v);
                    if (($v1 == '*' && $v2 == '*') || ($v1 == $m1 && ($v2 == $m2 || $v2 == '*'))) {
                        $allowed = true;
                        break;
                    }
                }
                foreach($this->forbidden as $k => $v) {
                    list($v1, $v2) = explode('/', $v);
                    if (($v1 == '*' && $v2 == '*') || ($v1 == $m1 && ($v2 == $m2 || $v2 == '*'))) {
                        $allowed = false;
                        break;
                    }
                }
                if (!$allowed) {
                    $this->processed = false;
                    $this->error = $this->translate('incorrect_file');
                } else {
                    $this->log .= '- file mime OK : ' . $this->file_src_mime . '<br />';
                }
            } else {
                $this->log .= '- file mime (not checked) : ' . $this->file_src_mime . '<br />';
            }
            if ($this->file_is_image) {
                if (is_numeric($this->image_src_x) && is_numeric($this->image_src_y)) {
                    $ratio = $this->image_src_x / $this->image_src_y;
                    if (!is_null($this->image_max_width) && $this->image_src_x > $this->image_max_width) {
                        $this->processed = false;
                        $this->error = $this->translate('image_too_wide');
                    }
                    if (!is_null($this->image_min_width) && $this->image_src_x < $this->image_min_width) {
                        $this->processed = false;
                        $this->error = $this->translate('image_too_narrow');
                    }
                    if (!is_null($this->image_max_height) && $this->image_src_y > $this->image_max_height) {
                        $this->processed = false;
                        $this->error = $this->translate('image_too_high');
                    }
                    if (!is_null($this->image_min_height) && $this->image_src_y < $this->image_min_height) {
                        $this->processed = false;
                        $this->error = $this->translate('image_too_short');
                    }
                    if (!is_null($this->image_max_ratio) && $ratio > $this->image_max_ratio) {
                        $this->processed = false;
                        $this->error = $this->translate('ratio_too_high');
                    }
                    if (!is_null($this->image_min_ratio) && $ratio < $this->image_min_ratio) {
                        $this->processed = false;
                        $this->error = $this->translate('ratio_too_low');
                    }
                    if (!is_null($this->image_max_pixels) && $this->image_src_pixels > $this->image_max_pixels) {
                        $this->processed = false;
                        $this->error = $this->translate('too_many_pixels');
                    }
                    if (!is_null($this->image_min_pixels) && $this->image_src_pixels < $this->image_min_pixels) {
                        $this->processed = false;
                        $this->error = $this->translate('not_enough_pixels');
                    }
                } else {
                    $this->log .= '- no image properties available, can\'t enforce dimension checks : ' . $this->file_src_mime . '<br />';
                }
            }
        }
        if ($this->processed) {
            $this->file_dst_path        = $server_path;
            $this->file_dst_name        = $this->file_src_name;
            $this->file_dst_name_body   = $this->file_src_name_body;
            $this->file_dst_name_ext    = $this->file_src_name_ext;
            if ($this->file_overwrite) $this->file_auto_rename = false;
            if ($this->image_convert != '') { // if we convert as an image
                $this->file_dst_name_ext  = $this->image_convert;
                $this->log .= '- new file name ext : ' . $this->image_convert . '<br />';
            }
            if ($this->file_new_name_body != '') { // rename file body
                $this->file_dst_name_body = $this->file_new_name_body;
                $this->log .= '- new file name body : ' . $this->file_new_name_body . '<br />';
            }
            if ($this->file_new_name_ext != '') { // rename file ext
                $this->file_dst_name_ext  = $this->file_new_name_ext;
                $this->log .= '- new file name ext : ' . $this->file_new_name_ext . '<br />';
            }
            if ($this->file_name_body_add != '') { // append a string to the name
                $this->file_dst_name_body  = $this->file_dst_name_body . $this->file_name_body_add;
                $this->log .= '- file name body append : ' . $this->file_name_body_add . '<br />';
            }
            if ($this->file_name_body_pre != '') { // prepend a string to the name
                $this->file_dst_name_body  = $this->file_name_body_pre . $this->file_dst_name_body;
                $this->log .= '- file name body prepend : ' . $this->file_name_body_pre . '<br />';
            }
            if ($this->file_safe_name) { // formats the name
                $this->file_dst_name_body = str_replace(array(' ', '-'), array('_','_'), $this->file_dst_name_body) ;
                $this->file_dst_name_body = preg_replace('/[^A-Za-z0-9_]/', '', $this->file_dst_name_body) ;
                $this->log .= '- file name safe format<br />';
            }
            $this->log .= '- destination variables<br />';
            if (empty($this->file_dst_path) || is_null($this->file_dst_path)) {
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_dst_path         : n/a<br />';
            } else {
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_dst_path         : ' . $this->file_dst_path . '<br />';
            }
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_dst_name_body    : ' . $this->file_dst_name_body . '<br />';
            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_dst_name_ext     : ' . $this->file_dst_name_ext . '<br />';
            $image_manipulation  = ($this->file_is_image && (
                                    $this->image_resize
                                 || $this->image_convert != ''
                                 || is_numeric($this->image_brightness)
                                 || is_numeric($this->image_contrast)
                                 || is_numeric($this->image_threshold)
                                 || !empty($this->image_tint_color)
                                 || !empty($this->image_overlay_color)
                                 || !empty($this->image_text)
                                 || $this->image_greyscale
                                 || $this->image_negative
                                 || !empty($this->image_watermark)
                                 || is_numeric($this->image_rotate)
                                 || is_numeric($this->jpeg_size)
                                 || !empty($this->image_flip)
                                 || !empty($this->image_crop)
                                 || !empty($this->image_precrop)
                                 || !empty($this->image_border)
                                 || $this->image_frame > 0
                                 || $this->image_bevel > 0
                                 || $this->image_reflection_height));
            if ($image_manipulation) {
                if ($this->image_convert=='') {
                    $this->file_dst_name = $this->file_dst_name_body . (!empty($this->file_dst_name_ext) ? '.' . $this->file_dst_name_ext : '');
                    $this->log .= '- image operation, keep extension<br />';
                } else {
                    $this->file_dst_name = $this->file_dst_name_body . '.' . $this->image_convert;
                    $this->log .= '- image operation, change extension for conversion type<br />';
                }
            } else {
                $this->file_dst_name = $this->file_dst_name_body . (!empty($this->file_dst_name_ext) ? '.' . $this->file_dst_name_ext : '');
                $this->log .= '- no image operation, keep extension<br />';
            }
            if (!$return_mode) {
                if (!$this->file_auto_rename) {
                    $this->log .= '- no auto_rename if same filename exists<br />';
                    $this->file_dst_pathname = $this->file_dst_path . $this->file_dst_name;
                } else {
                    $this->log .= '- checking for auto_rename<br />';
                    $this->file_dst_pathname = $this->file_dst_path . $this->file_dst_name;
                    $body     = $this->file_dst_name_body;
                    $cpt = 1;
                    while (@file_exists($this->file_dst_pathname)) {
                        $this->file_dst_name_body = $body . '_' . $cpt;
                        $this->file_dst_name = $this->file_dst_name_body . (!empty($this->file_dst_name_ext) ? '.' . $this->file_dst_name_ext : '');
                        $cpt++;
                        $this->file_dst_pathname = $this->file_dst_path . $this->file_dst_name;
                    }
                    if ($cpt>1) $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;auto_rename to ' . $this->file_dst_name . '<br />';
                }
                $this->log .= '- destination file details<br />';
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_dst_name         : ' . $this->file_dst_name . '<br />';
                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;file_dst_pathname     : ' . $this->file_dst_pathname . '<br />';
                if ($this->file_overwrite) {
                     $this->log .= '- no overwrite checking<br />';
                } else {
                    if (@file_exists($this->file_dst_pathname)) {
                        $this->processed = false;
                        $this->error = $this->translate('already_exists', array($this->file_dst_name));
                    } else {
                        $this->log .= '- ' . $this->file_dst_name . ' doesn\'t exist already<br />';
                    }
                }
            }
        }
        if ($this->processed) {
            if (!empty($this->file_src_temp)) {
                $this->log .= '- use the temp file instead of the original file since it is a second process<br />';
                $this->file_src_pathname   = $this->file_src_temp;
                if (!file_exists($this->file_src_pathname)) {
                    $this->processed = false;
                    $this->error = $this->translate('temp_file_missing');
                }
            } else if (!$this->no_upload_check) {
                if (!is_uploaded_file($this->file_src_pathname)) {
                    $this->processed = false;
                    $this->error = $this->translate('source_missing');
                }
            } else {
                if (!file_exists($this->file_src_pathname)) {
                    $this->processed = false;
                    $this->error = $this->translate('source_missing');
                }
            }
            if (!$return_mode) {
                if ($this->processed && !file_exists($this->file_dst_path)) {
                    if ($this->dir_auto_create) {
                        $this->log .= '- ' . $this->file_dst_path . ' doesn\'t exist. Attempting creation:';
                        if (!$this->rmkdir($this->file_dst_path, $this->dir_chmod)) {
                            $this->log .= ' failed<br />';
                            $this->processed = false;
                            $this->error = $this->translate('destination_dir');
                        } else {
                            $this->log .= ' success<br />';
                        }
                    } else {
                        $this->error = $this->translate('destination_dir_missing');
                    }
                }
                if ($this->processed && !is_dir($this->file_dst_path)) {
                    $this->processed = false;
                    $this->error = $this->translate('destination_path_not_dir');
                }
                $hash = md5($this->file_dst_name_body . rand(1, 1000));
                if ($this->processed && !($f = @fopen($this->file_dst_path . $hash . '.' . $this->file_dst_name_ext, 'a+'))) {
                    if ($this->dir_auto_chmod) {
                        $this->log .= '- ' . $this->file_dst_path . ' is not writeable. Attempting chmod:';
                        if (!@chmod($this->file_dst_path, $this->dir_chmod)) {
                            $this->log .= ' failed<br />';
                            $this->processed = false;
                            $this->error = $this->translate('destination_dir_write');
                        } else {
                            $this->log .= ' success<br />';
                            if (!($f = @fopen($this->file_dst_path . $hash . '.' . $this->file_dst_name_ext, 'a+'))) { // we re-check
                                $this->processed = false;
                                $this->error = $this->translate('destination_dir_write');
                            } else {
                                @fclose($f);
                            }
                        }
                    } else {
                        $this->processed = false;
                        $this->error = $this->translate('destination_path_write');
                    }
                } else {
                    if ($this->processed) @fclose($f);
                    @unlink($this->file_dst_path . $hash . '.' . $this->file_dst_name_ext);
                }
                if (!$this->no_upload_check && empty($this->file_src_temp) && !@file_exists($this->file_src_pathname)) {
                    $this->log .= '- attempting to use a temp file:';
                    $hash = md5($this->file_dst_name_body . rand(1, 1000));
                    if (move_uploaded_file($this->file_src_pathname, $this->file_dst_path . $hash . '.' . $this->file_dst_name_ext)) {
                        $this->file_src_pathname = $this->file_dst_path . $hash . '.' . $this->file_dst_name_ext;
                        $this->file_src_temp = $this->file_src_pathname;
                        $this->log .= ' file created<br />';
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;temp file is: ' . $this->file_src_temp . '<br />';
                    } else {
                        $this->log .= ' failed<br />';
                        $this->processed = false;
                        $this->error = $this->translate('temp_file');
                    }
                }
            }
        }
        if ($this->processed) {
            if ($image_manipulation && !@getimagesize($this->file_src_pathname)) {
                $this->log .= '- the file is not an image!<br />';
                $image_manipulation = false;
            }
            if ($image_manipulation) {
                if ($this->processed && !($f = @fopen($this->file_src_pathname, 'r'))) {
                    $this->processed = false;
                    $this->error = $this->translate('source_not_readable');
                } else {
                    @fclose($f);
                }
                $this->log .= '- image resizing or conversion wanted<br />';
                if ($this->gdversion()) {
                    switch($this->image_src_type) {
                        case 'jpg':
                            if (!function_exists('imagecreatefromjpeg')) {
                                $this->processed = false;
                                $this->error = $this->translate('no_create_support', array('JPEG'));
                            } else {
                                $image_src = @imagecreatefromjpeg($this->file_src_pathname);
                                if (!$image_src) {
                                    $this->processed = false;
                                    $this->error = $this->translate('create_error', array('JPEG'));
                                } else {
                                    $this->log .= '- source image is JPEG<br />';
                                }
                            }
                            break;
                        case 'png':
                            if (!function_exists('imagecreatefrompng')) {
                                $this->processed = false;
                                $this->error = $this->translate('no_create_support', array('PNG'));
                            } else {
                                $image_src = @imagecreatefrompng($this->file_src_pathname);
                                if (!$image_src) {
                                    $this->processed = false;
                                    $this->error = $this->translate('create_error', array('PNG'));
                                } else {
                                    $this->log .= '- source image is PNG<br />';
                                }
                            }
                            break;
                        case 'gif':
                            if (!function_exists('imagecreatefromgif')) {
                                $this->processed = false;
                                $this->error = $this->translate('no_create_support', array('GIF'));
                            } else {
                                $image_src = @imagecreatefromgif($this->file_src_pathname);
                                if (!$image_src) {
                                    $this->processed = false;
                                    $this->error = $this->translate('create_error', array('GIF'));
                                } else {
                                    $this->log .= '- source image is GIF<br />';
                                }
                            }
                            break;
                        case 'bmp':
                            if (!method_exists($this, 'imagecreatefrombmp')) {
                                $this->processed = false;
                                $this->error = $this->translate('no_create_support', array('BMP'));
                            } else {
                                $image_src = @$this->imagecreatefrombmp($this->file_src_pathname);
                                if (!$image_src) {
                                    $this->processed = false;
                                    $this->error = $this->translate('create_error', array('BMP'));
                                } else {
                                    $this->log .= '- source image is BMP<br />';
                                }
                            }
                            break;
                        default:
                            $this->processed = false;
                            $this->error = $this->translate('source_invalid');
                    }
                } else {
                    $this->processed = false;
                    $this->error = $this->translate('gd_missing');
                }
                if ($this->processed && $image_src) {
                    if (empty($this->image_convert)) {
                        $this->log .= '- setting destination file type to ' . $this->file_src_name_ext . '<br />';
                        $this->image_convert = $this->file_src_name_ext;
                    }
                    if (!in_array($this->image_convert, $this->image_supported)) {
                        $this->image_convert = 'jpg';
                    }
                    if ($this->image_convert != 'png' && $this->image_convert != 'gif' && !empty($this->image_default_color) && empty($this->image_background_color)) $this->image_background_color = $this->image_default_color;
                    if (!empty($this->image_background_color)) $this->image_default_color = $this->image_background_color;
                    if (empty($this->image_default_color)) $this->image_default_color = '#FFFFFF';
                    $this->image_src_x = imagesx($image_src);
                    $this->image_src_y = imagesy($image_src);
                    $gd_version = $this->gdversion();
                    $ratio_crop = null;
                    if (!imageistruecolor($image_src)) {  // $this->image_src_type == 'gif'
                        $this->log .= '- image is detected as having a palette<br />';
                        $this->image_is_palette = true;
                        $this->image_transparent_color = imagecolortransparent($image_src);
                        if ($this->image_transparent_color >= 0 && imagecolorstotal($image_src) > $this->image_transparent_color) {
                            $this->image_is_transparent = true;
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;palette image is detected as transparent<br />';
                        }
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;convert palette image to true color<br />';
                        $true_color = imagecreatetruecolor($this->image_src_x, $this->image_src_y);
                        imagealphablending($true_color, false);
                        imagesavealpha($true_color, true);
                        for ($x = 0; $x < $this->image_src_x; $x++) {
                            for ($y = 0; $y < $this->image_src_y; $y++) {
                                if ($this->image_transparent_color >= 0 && imagecolorat($image_src, $x, $y) == $this->image_transparent_color) {
                                    imagesetpixel($true_color, $x, $y, 127 << 24);
                                } else {
                                    $rgb = imagecolorsforindex($image_src, imagecolorat($image_src, $x, $y));
                                    imagesetpixel($true_color, $x, $y, ($rgb['alpha'] << 24) | ($rgb['red'] << 16) | ($rgb['green'] << 8) | $rgb['blue']);
                                }
                            }
                        }
                        $image_src = $this->imagetransfer($true_color, $image_src);
                        imagealphablending($image_src, false);
                        imagesavealpha($image_src, true);
                        $this->image_is_palette = false;
                    }
                    $image_dst = & $image_src;
                    if ((!empty($this->image_precrop))) {
                        if (is_array($this->image_precrop)) {
                            $vars = $this->image_precrop;
                        } else {
                            $vars = explode(' ', $this->image_precrop);
                        }
                        if (sizeof($vars) == 4) {
                            $ct = $vars[0]; $cr = $vars[1]; $cb = $vars[2]; $cl = $vars[3];
                        } else if (sizeof($vars) == 2) {
                            $ct = $vars[0]; $cr = $vars[1]; $cb = $vars[0]; $cl = $vars[1];
                        } else {
                            $ct = $vars[0]; $cr = $vars[0]; $cb = $vars[0]; $cl = $vars[0];
                        }
                        if (strpos($ct, '%')>0) $ct = $this->image_src_y * (str_replace('%','',$ct) / 100);
                        if (strpos($cr, '%')>0) $cr = $this->image_src_x * (str_replace('%','',$cr) / 100);
                        if (strpos($cb, '%')>0) $cb = $this->image_src_y * (str_replace('%','',$cb) / 100);
                        if (strpos($cl, '%')>0) $cl = $this->image_src_x * (str_replace('%','',$cl) / 100);
                        if (strpos($ct, 'px')>0) $ct = str_replace('px','',$ct);
                        if (strpos($cr, 'px')>0) $cr = str_replace('px','',$cr);
                        if (strpos($cb, 'px')>0) $cb = str_replace('px','',$cb);
                        if (strpos($cl, 'px')>0) $cl = str_replace('px','',$cl);
                        $ct = (int) $ct;
                        $cr = (int) $cr;
                        $cb = (int) $cb;
                        $cl = (int) $cl;
                        $this->log .= '- pre-crop image : ' . $ct . ' ' . $cr . ' ' . $cb . ' ' . $cl . ' <br />';
                        $this->image_src_x = $this->image_src_x - $cl - $cr;
                        $this->image_src_y = $this->image_src_y - $ct - $cb;
                        if ($this->image_src_x < 1) $this->image_src_x = 1;
                        if ($this->image_src_y < 1) $this->image_src_y = 1;
                        $tmp = $this->imagecreatenew($this->image_src_x, $this->image_src_y);
                        imagecopy($tmp, $image_dst, 0, 0, $cl, $ct, $this->image_src_x, $this->image_src_y);
                        if ($ct < 0 || $cr < 0 || $cb < 0 || $cl < 0 ) {
                            if (!empty($this->image_background_color)) {
                                list($red, $green, $blue) = $this->getcolors($this->image_background_color);
                                $fill = imagecolorallocate($tmp, $red, $green, $blue);
                            } else {
                                $fill = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
                            }
                            if ($ct < 0) imagefilledrectangle($tmp, 0, 0, $this->image_src_x, -$ct, $fill);
                            if ($cr < 0) imagefilledrectangle($tmp, $this->image_src_x + $cr, 0, $this->image_src_x, $this->image_src_y, $fill);
                            if ($cb < 0) imagefilledrectangle($tmp, 0, $this->image_src_y + $cb, $this->image_src_x, $this->image_src_y, $fill);
                            if ($cl < 0) imagefilledrectangle($tmp, 0, 0, -$cl, $this->image_src_y, $fill);
                        }
                        $image_dst = $this->imagetransfer($tmp, $image_dst);
                    }
                    if ($this->image_resize) {
                        $this->log .= '- resizing...<br />';

                        if ($this->image_ratio_x) {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;calculate x size<br />';
                            $this->image_dst_x = round(($this->image_src_x * $this->image_y) / $this->image_src_y);
                            $this->image_dst_y = $this->image_y;
                        } else if ($this->image_ratio_y) {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;calculate y size<br />';
                            $this->image_dst_x = $this->image_x;
                            $this->image_dst_y = round(($this->image_src_y * $this->image_x) / $this->image_src_x);
                        } else if (is_numeric($this->image_ratio_pixels)) {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;calculate x/y size to match a number of pixels<br />';
                            $pixels = $this->image_src_y * $this->image_src_x;
                            $diff = sqrt($this->image_ratio_pixels / $pixels);
                            $this->image_dst_x = round($this->image_src_x * $diff);
                            $this->image_dst_y = round($this->image_src_y * $diff);
                        } else if ($this->image_ratio || $this->image_ratio_crop || $this->image_ratio_fill || $this->image_ratio_no_zoom_in || $this->image_ratio_no_zoom_out) {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;check x/y sizes<br />';
                            if ((!$this->image_ratio_no_zoom_in && !$this->image_ratio_no_zoom_out)
                                 || ($this->image_ratio_no_zoom_in && ($this->image_src_x > $this->image_x || $this->image_src_y > $this->image_y))
                                 || ($this->image_ratio_no_zoom_out && $this->image_src_x < $this->image_x && $this->image_src_y < $this->image_y)) {
                                $this->image_dst_x = $this->image_x;
                                $this->image_dst_y = $this->image_y;
                                if ($this->image_ratio_crop) {
                                    if (!is_string($this->image_ratio_crop)) $this->image_ratio_crop = '';
                                    $this->image_ratio_crop = strtolower($this->image_ratio_crop);
                                    if (($this->image_src_x/$this->image_x) > ($this->image_src_y/$this->image_y)) {
                                        $this->image_dst_y = $this->image_y;
                                        $this->image_dst_x = intval($this->image_src_x*($this->image_y / $this->image_src_y));
                                        $ratio_crop = array();
                                        $ratio_crop['x'] = $this->image_dst_x - $this->image_x;
                                        if (strpos($this->image_ratio_crop, 'l') !== false) {
                                            $ratio_crop['l'] = 0;
                                            $ratio_crop['r'] = $ratio_crop['x'];
                                        } else if (strpos($this->image_ratio_crop, 'r') !== false) {
                                            $ratio_crop['l'] = $ratio_crop['x'];
                                            $ratio_crop['r'] = 0;
                                        } else {
                                            $ratio_crop['l'] = round($ratio_crop['x']/2);
                                            $ratio_crop['r'] = $ratio_crop['x'] - $ratio_crop['l'];
                                        }
                                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;ratio_crop_x         : ' . $ratio_crop['x'] . ' (' . $ratio_crop['l'] . ';' . $ratio_crop['r'] . ')<br />';
                                        if (is_null($this->image_crop)) $this->image_crop = array(0, 0, 0, 0);
                                    } else {
                                        $this->image_dst_x = $this->image_x;
                                        $this->image_dst_y = intval($this->image_src_y*($this->image_x / $this->image_src_x));
                                        $ratio_crop = array();
                                        $ratio_crop['y'] = $this->image_dst_y - $this->image_y;
                                        if (strpos($this->image_ratio_crop, 't') !== false) {
                                            $ratio_crop['t'] = 0;
                                            $ratio_crop['b'] = $ratio_crop['y'];
                                        } else if (strpos($this->image_ratio_crop, 'b') !== false) {
                                            $ratio_crop['t'] = $ratio_crop['y'];
                                            $ratio_crop['b'] = 0;
                                        } else {
                                            $ratio_crop['t'] = round($ratio_crop['y']/2);
                                            $ratio_crop['b'] = $ratio_crop['y'] - $ratio_crop['t'];
                                        }
                                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;ratio_crop_y         : ' . $ratio_crop['y'] . ' (' . $ratio_crop['t'] . ';' . $ratio_crop['b'] . ')<br />';
                                        if (is_null($this->image_crop)) $this->image_crop = array(0, 0, 0, 0);
                                    }
                                } else if ($this->image_ratio_fill) {
                                    if (!is_string($this->image_ratio_fill)) $this->image_ratio_fill = '';
                                    $this->image_ratio_fill = strtolower($this->image_ratio_fill);
                                    if (($this->image_src_x/$this->image_x) < ($this->image_src_y/$this->image_y)) {
                                        $this->image_dst_y = $this->image_y;
                                        $this->image_dst_x = intval($this->image_src_x*($this->image_y / $this->image_src_y));
                                        $ratio_crop = array();
                                        $ratio_crop['x'] = $this->image_dst_x - $this->image_x;
                                        if (strpos($this->image_ratio_fill, 'l') !== false) {
                                            $ratio_crop['l'] = 0;
                                            $ratio_crop['r'] = $ratio_crop['x'];
                                        } else if (strpos($this->image_ratio_fill, 'r') !== false) {
                                            $ratio_crop['l'] = $ratio_crop['x'];
                                            $ratio_crop['r'] = 0;
                                        } else {
                                            $ratio_crop['l'] = round($ratio_crop['x']/2);
                                            $ratio_crop['r'] = $ratio_crop['x'] - $ratio_crop['l'];
                                        }
                                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;ratio_fill_x         : ' . $ratio_crop['x'] . ' (' . $ratio_crop['l'] . ';' . $ratio_crop['r'] . ')<br />';
                                        if (is_null($this->image_crop)) $this->image_crop = array(0, 0, 0, 0);
                                    } else {
                                        $this->image_dst_x = $this->image_x;
                                        $this->image_dst_y = intval($this->image_src_y*($this->image_x / $this->image_src_x));
                                        $ratio_crop = array();
                                        $ratio_crop['y'] = $this->image_dst_y - $this->image_y;
                                        if (strpos($this->image_ratio_fill, 't') !== false) {
                                            $ratio_crop['t'] = 0;
                                            $ratio_crop['b'] = $ratio_crop['y'];
                                        } else if (strpos($this->image_ratio_fill, 'b') !== false) {
                                            $ratio_crop['t'] = $ratio_crop['y'];
                                            $ratio_crop['b'] = 0;
                                        } else {
                                            $ratio_crop['t'] = round($ratio_crop['y']/2);
                                            $ratio_crop['b'] = $ratio_crop['y'] - $ratio_crop['t'];
                                        }
                                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;ratio_fill_y         : ' . $ratio_crop['y'] . ' (' . $ratio_crop['t'] . ';' . $ratio_crop['b'] . ')<br />';
                                        if (is_null($this->image_crop)) $this->image_crop = array(0, 0, 0, 0);
                                    }
                                } else {
                                    if (($this->image_src_x/$this->image_x) > ($this->image_src_y/$this->image_y)) {
                                        $this->image_dst_x = $this->image_x;
                                        $this->image_dst_y = intval($this->image_src_y*($this->image_x / $this->image_src_x));
                                    } else {
                                        $this->image_dst_y = $this->image_y;
                                        $this->image_dst_x = intval($this->image_src_x*($this->image_y / $this->image_src_y));
                                    }
                                }
                            } else {
                                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;doesn\'t calculate x/y sizes<br />';
                                $this->image_dst_x = $this->image_src_x;
                                $this->image_dst_y = $this->image_src_y;
                            }
                        } else {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;use plain sizes<br />';
                            $this->image_dst_x = $this->image_x;
                            $this->image_dst_y = $this->image_y;
                        }

                        if ($this->image_dst_x < 1) $this->image_dst_x = 1;
                        if ($this->image_dst_y < 1) $this->image_dst_y = 1;
                        $tmp = $this->imagecreatenew($this->image_dst_x, $this->image_dst_y);

                        if ($gd_version >= 2) {
                            $res = imagecopyresampled($tmp, $image_src, 0, 0, 0, 0, $this->image_dst_x, $this->image_dst_y, $this->image_src_x, $this->image_src_y);
                        } else {
                            $res = imagecopyresized($tmp, $image_src, 0, 0, 0, 0, $this->image_dst_x, $this->image_dst_y, $this->image_src_x, $this->image_src_y);
                        }
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;resized image object created<br />';
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;image_src_x y        : ' . $this->image_src_x . ' x ' . $this->image_src_y . '<br />';
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;image_dst_x y        : ' . $this->image_dst_x . ' x ' . $this->image_dst_y . '<br />';
                        $image_dst = $this->imagetransfer($tmp, $image_dst);
                    } else {
                        $this->image_dst_x = $this->image_src_x;
                        $this->image_dst_y = $this->image_src_y;
                    }
                    if ((!empty($this->image_crop) || !is_null($ratio_crop))) {
                        if (is_array($this->image_crop)) {
                            $vars = $this->image_crop;
                        } else {
                            $vars = explode(' ', $this->image_crop);
                        }
                        if (sizeof($vars) == 4) {
                            $ct = $vars[0]; $cr = $vars[1]; $cb = $vars[2]; $cl = $vars[3];
                        } else if (sizeof($vars) == 2) {
                            $ct = $vars[0]; $cr = $vars[1]; $cb = $vars[0]; $cl = $vars[1];
                        } else {
                            $ct = $vars[0]; $cr = $vars[0]; $cb = $vars[0]; $cl = $vars[0];
                        }
                        if (strpos($ct, '%')>0) $ct = $this->image_dst_y * (str_replace('%','',$ct) / 100);
                        if (strpos($cr, '%')>0) $cr = $this->image_dst_x * (str_replace('%','',$cr) / 100);
                        if (strpos($cb, '%')>0) $cb = $this->image_dst_y * (str_replace('%','',$cb) / 100);
                        if (strpos($cl, '%')>0) $cl = $this->image_dst_x * (str_replace('%','',$cl) / 100);
                        if (strpos($ct, 'px')>0) $ct = str_replace('px','',$ct);
                        if (strpos($cr, 'px')>0) $cr = str_replace('px','',$cr);
                        if (strpos($cb, 'px')>0) $cb = str_replace('px','',$cb);
                        if (strpos($cl, 'px')>0) $cl = str_replace('px','',$cl);
                        $ct = (int) $ct;
                        $cr = (int) $cr;
                        $cb = (int) $cb;
                        $cl = (int) $cl;
                        if (!is_null($ratio_crop)) {
                            if (array_key_exists('t', $ratio_crop)) $ct += $ratio_crop['t'];
                            if (array_key_exists('r', $ratio_crop)) $cr += $ratio_crop['r'];
                            if (array_key_exists('b', $ratio_crop)) $cb += $ratio_crop['b'];
                            if (array_key_exists('l', $ratio_crop)) $cl += $ratio_crop['l'];
                        }
                        $this->log .= '- crop image : ' . $ct . ' ' . $cr . ' ' . $cb . ' ' . $cl . ' <br />';
                        $this->image_dst_x = $this->image_dst_x - $cl - $cr;
                        $this->image_dst_y = $this->image_dst_y - $ct - $cb;
                        if ($this->image_dst_x < 1) $this->image_dst_x = 1;
                        if ($this->image_dst_y < 1) $this->image_dst_y = 1;
                        $tmp = $this->imagecreatenew($this->image_dst_x, $this->image_dst_y);
                        imagecopy($tmp, $image_dst, 0, 0, $cl, $ct, $this->image_dst_x, $this->image_dst_y);
                        if ($ct < 0 || $cr < 0 || $cb < 0 || $cl < 0 ) {
                            if (!empty($this->image_background_color)) {
                                list($red, $green, $blue) = $this->getcolors($this->image_background_color);
                                $fill = imagecolorallocate($tmp, $red, $green, $blue);
                            } else {
                                $fill = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
                            }
                            if ($ct < 0) imagefilledrectangle($tmp, 0, 0, $this->image_dst_x, -$ct, $fill);
                            if ($cr < 0) imagefilledrectangle($tmp, $this->image_dst_x + $cr, 0, $this->image_dst_x, $this->image_dst_y, $fill);
                            if ($cb < 0) imagefilledrectangle($tmp, 0, $this->image_dst_y + $cb, $this->image_dst_x, $this->image_dst_y, $fill);
                            if ($cl < 0) imagefilledrectangle($tmp, 0, 0, -$cl, $this->image_dst_y, $fill);
                        }
                        $image_dst = $this->imagetransfer($tmp, $image_dst);
                    }
                    if ($gd_version >= 2 && !empty($this->image_flip)) {
                        $this->image_flip = strtolower($this->image_flip);
                        $this->log .= '- flip image : ' . $this->image_flip . '<br />';
                        $tmp = $this->imagecreatenew($this->image_dst_x, $this->image_dst_y);
                        for ($x = 0; $x < $this->image_dst_x; $x++) {
                            for ($y = 0; $y < $this->image_dst_y; $y++){
                                if (strpos($this->image_flip, 'v') !== false) {
                                    imagecopy($tmp, $image_dst, $this->image_dst_x - $x - 1, $y, $x, $y, 1, 1);
                                } else {
                                    imagecopy($tmp, $image_dst, $x, $this->image_dst_y - $y - 1, $x, $y, 1, 1);
                                }
                            }
                        }
                        $image_dst = $this->imagetransfer($tmp, $image_dst);
                    }
                    if ($gd_version >= 2 && is_numeric($this->image_rotate)) {
                        if (!in_array($this->image_rotate, array(0, 90, 180, 270))) $this->image_rotate = 0;
                        if ($this->image_rotate != 0) {
                            if ($this->image_rotate == 90 || $this->image_rotate == 270) {
                                $tmp = $this->imagecreatenew($this->image_dst_y, $this->image_dst_x);
                            } else {
                                $tmp = $this->imagecreatenew($this->image_dst_x, $this->image_dst_y);
                            }
                            $this->log .= '- rotate image : ' . $this->image_rotate . '<br />';
                            for ($x = 0; $x < $this->image_dst_x; $x++) {
                                for ($y = 0; $y < $this->image_dst_y; $y++){
                                    if ($this->image_rotate == 90) {
                                        imagecopy($tmp, $image_dst, $y, $x, $x, $this->image_dst_y - $y - 1, 1, 1);
                                    } else if ($this->image_rotate == 180) {
                                        imagecopy($tmp, $image_dst, $x, $y, $this->image_dst_x - $x - 1, $this->image_dst_y - $y - 1, 1, 1);
                                    } else if ($this->image_rotate == 270) {
                                        imagecopy($tmp, $image_dst, $y, $x, $this->image_dst_x - $x - 1, $y, 1, 1);
                                    } else {
                                        imagecopy($tmp, $image_dst, $x, $y, $x, $y, 1, 1);
                                    }
                                }
                            }
                            if ($this->image_rotate == 90 || $this->image_rotate == 270) {
                                $t = $this->image_dst_y;
                                $this->image_dst_y = $this->image_dst_x;
                                $this->image_dst_x = $t;
                            }
                            $image_dst = $this->imagetransfer($tmp, $image_dst);
                        }
                    }
                   if ($gd_version >= 2 && (is_numeric($this->image_overlay_percent) && $this->image_overlay_percent > 0 && !empty($this->image_overlay_color))) {
                        $this->log .= '- apply color overlay<br />';
                        list($red, $green, $blue) = $this->getcolors($this->image_overlay_color);
                        $filter = imagecreatetruecolor($this->image_dst_x, $this->image_dst_y);
                        $color = imagecolorallocate($filter, $red, $green, $blue);
                        imagefilledrectangle($filter, 0, 0, $this->image_dst_x, $this->image_dst_y, $color);
                        $this->imagecopymergealpha($image_dst, $filter, 0, 0, 0, 0, $this->image_dst_x, $this->image_dst_y, $this->image_overlay_percent);
                        imagedestroy($filter);
                    }
                    if ($gd_version >= 2 && ($this->image_negative || $this->image_greyscale || is_numeric($this->image_threshold)|| is_numeric($this->image_brightness) || is_numeric($this->image_contrast) || !empty($this->image_tint_color))) {
                        $this->log .= '- apply tint, light, contrast correction, negative, greyscale and threshold<br />';
                        if (!empty($this->image_tint_color)) list($tint_red, $tint_green, $tint_blue) = $this->getcolors($this->image_tint_color);
                        imagealphablending($image_dst, true);
                        for($y=0; $y < $this->image_dst_y; $y++) {
                            for($x=0; $x < $this->image_dst_x; $x++) {
                                if ($this->image_greyscale) {
                                    $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                    $r = $g = $b = round((0.2125 * $pixel['red']) + (0.7154 * $pixel['green']) + (0.0721 * $pixel['blue']));
                                    $color = imagecolorallocatealpha($image_dst, $r, $g, $b, $pixel['alpha']);
                                    imagesetpixel($image_dst, $x, $y, $color);
                                }
                                if (is_numeric($this->image_threshold)) {
                                    $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                    $c = (round($pixel['red'] + $pixel['green'] + $pixel['blue']) / 3) - 127;
                                    $r = $g = $b = ($c > $this->image_threshold ? 255 : 0);
                                    $color = imagecolorallocatealpha($image_dst, $r, $g, $b, $pixel['alpha']);
                                    imagesetpixel($image_dst, $x, $y, $color);
                                }
                                if (is_numeric($this->image_brightness)) {
                                    $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                    $r = max(min(round($pixel['red'] + (($this->image_brightness * 2))), 255), 0);
                                    $g = max(min(round($pixel['green'] + (($this->image_brightness * 2))), 255), 0);
                                    $b = max(min(round($pixel['blue'] + (($this->image_brightness * 2))), 255), 0);
                                    $color = imagecolorallocatealpha($image_dst, $r, $g, $b, $pixel['alpha']);
                                    imagesetpixel($image_dst, $x, $y, $color);
                                }
                                if (is_numeric($this->image_contrast)) {
                                    $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                    $r = max(min(round(($this->image_contrast + 128) * $pixel['red'] / 128), 255), 0);
                                    $g = max(min(round(($this->image_contrast + 128) * $pixel['green'] / 128), 255), 0);
                                    $b = max(min(round(($this->image_contrast + 128) * $pixel['blue'] / 128), 255), 0);
                                    $color = imagecolorallocatealpha($image_dst, $r, $g, $b, $pixel['alpha']);
                                    imagesetpixel($image_dst, $x, $y, $color);
                                }
                                if (!empty($this->image_tint_color)) {
                                    $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                    $r = min(round($tint_red * $pixel['red'] / 169), 255);
                                    $g = min(round($tint_green * $pixel['green'] / 169), 255);
                                    $b = min(round($tint_blue * $pixel['blue'] / 169), 255);
                                    $color = imagecolorallocatealpha($image_dst, $r, $g, $b, $pixel['alpha']);
                                    imagesetpixel($image_dst, $x, $y, $color);
                                }
                                if (!empty($this->image_negative)) {
                                    $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                    $r = round(255 - $pixel['red']);
                                    $g = round(255 - $pixel['green']);
                                    $b = round(255 - $pixel['blue']);
                                    $color = imagecolorallocatealpha($image_dst, $r, $g, $b, $pixel['alpha']);
                                    imagesetpixel($image_dst, $x, $y, $color);
                                }
                            }
                        }
                    }
                    if ($gd_version >= 2 && !empty($this->image_border)) {
                        if (is_array($this->image_border)) {
                            $vars = $this->image_border;
                            $this->log .= '- add border : ' . implode(' ', $this->image_border) . '<br />';
                        } else {
                            $this->log .= '- add border : ' . $this->image_border . '<br />';
                            $vars = explode(' ', $this->image_border);
                        }
                        if (sizeof($vars) == 4) {
                            $ct = $vars[0]; $cr = $vars[1]; $cb = $vars[2]; $cl = $vars[3];
                        } else if (sizeof($vars) == 2) {
                            $ct = $vars[0]; $cr = $vars[1]; $cb = $vars[0]; $cl = $vars[1];
                        } else {
                            $ct = $vars[0]; $cr = $vars[0]; $cb = $vars[0]; $cl = $vars[0];
                        }
                        if (strpos($ct, '%')>0) $ct = $this->image_dst_y * (str_replace('%','',$ct) / 100);
                        if (strpos($cr, '%')>0) $cr = $this->image_dst_x * (str_replace('%','',$cr) / 100);
                        if (strpos($cb, '%')>0) $cb = $this->image_dst_y * (str_replace('%','',$cb) / 100);
                        if (strpos($cl, '%')>0) $cl = $this->image_dst_x * (str_replace('%','',$cl) / 100);
                        if (strpos($ct, 'px')>0) $ct = str_replace('px','',$ct);
                        if (strpos($cr, 'px')>0) $cr = str_replace('px','',$cr);
                        if (strpos($cb, 'px')>0) $cb = str_replace('px','',$cb);
                        if (strpos($cl, 'px')>0) $cl = str_replace('px','',$cl);
                        $ct = (int) $ct;
                        $cr = (int) $cr;
                        $cb = (int) $cb;
                        $cl = (int) $cl;
                        $this->image_dst_x = $this->image_dst_x + $cl + $cr;
                        $this->image_dst_y = $this->image_dst_y + $ct + $cb;
                        if (!empty($this->image_border_color)) list($red, $green, $blue) = $this->getcolors($this->image_border_color);
                        $tmp = $this->imagecreatenew($this->image_dst_x, $this->image_dst_y);
                        $background = imagecolorallocatealpha($tmp, $red, $green, $blue, 0);
                        imagefilledrectangle($tmp, 0, 0, $this->image_dst_x, $this->image_dst_y, $background);
                        imagecopy($tmp, $image_dst, $cl, $ct, 0, 0, $this->image_dst_x - $cr - $cl, $this->image_dst_y - $cb - $ct);
                        $image_dst = $this->imagetransfer($tmp, $image_dst);
                    }
                    if (is_numeric($this->image_frame)) {
                        if (is_array($this->image_frame_colors)) {
                            $vars = $this->image_frame_colors;
                            $this->log .= '- add frame : ' . implode(' ', $this->image_frame_colors) . '<br />';
                        } else {
                            $this->log .= '- add frame : ' . $this->image_frame_colors . '<br />';
                            $vars = explode(' ', $this->image_frame_colors);
                        }
                        $nb = sizeof($vars);
                        $this->image_dst_x = $this->image_dst_x + ($nb * 2);
                        $this->image_dst_y = $this->image_dst_y + ($nb * 2);
                        $tmp = $this->imagecreatenew($this->image_dst_x, $this->image_dst_y);
                        imagecopy($tmp, $image_dst, $nb, $nb, 0, 0, $this->image_dst_x - ($nb * 2), $this->image_dst_y - ($nb * 2));
                        for ($i=0; $i<$nb; $i++) {
                            list($red, $green, $blue) = $this->getcolors($vars[$i]);
                            $c = imagecolorallocate($tmp, $red, $green, $blue);
                            if ($this->image_frame == 1) {
                                imageline($tmp, $i, $i, $this->image_dst_x - $i -1, $i, $c);
                                imageline($tmp, $this->image_dst_x - $i -1, $this->image_dst_y - $i -1, $this->image_dst_x - $i -1, $i, $c);
                                imageline($tmp, $this->image_dst_x - $i -1, $this->image_dst_y - $i -1, $i, $this->image_dst_y - $i -1, $c);
                                imageline($tmp, $i, $i, $i, $this->image_dst_y - $i -1, $c);
                            } else {
                                imageline($tmp, $i, $i, $this->image_dst_x - $i -1, $i, $c);
                                imageline($tmp, $this->image_dst_x - $nb + $i, $this->image_dst_y - $nb + $i, $this->image_dst_x - $nb + $i, $nb - $i, $c);
                                imageline($tmp, $this->image_dst_x - $nb + $i, $this->image_dst_y - $nb + $i, $nb - $i, $this->image_dst_y - $nb + $i, $c);
                                imageline($tmp, $i, $i, $i, $this->image_dst_y - $i -1, $c);
                            }
                        }
                        $image_dst = $this->imagetransfer($tmp, $image_dst);
                    }
                    if ($this->image_bevel > 0) {
                        if (empty($this->image_bevel_color1)) $this->image_bevel_color1 = '#FFFFFF';
                        if (empty($this->image_bevel_color2)) $this->image_bevel_color2 = '#000000';
                        list($red1, $green1, $blue1) = $this->getcolors($this->image_bevel_color1);
                        list($red2, $green2, $blue2) = $this->getcolors($this->image_bevel_color2);
                        $tmp = $this->imagecreatenew($this->image_dst_x, $this->image_dst_y);
                        imagecopy($tmp, $image_dst, 0, 0, 0, 0, $this->image_dst_x, $this->image_dst_y);
                        imagealphablending($tmp, true);
                        for ($i=0; $i<$this->image_bevel; $i++) {
                            $alpha = round(($i / $this->image_bevel) * 127);
                            $c1 = imagecolorallocatealpha($tmp, $red1, $green1, $blue1, $alpha);
                            $c2 = imagecolorallocatealpha($tmp, $red2, $green2, $blue2, $alpha);
                            imageline($tmp, $i, $i, $this->image_dst_x - $i -1, $i, $c1);
                            imageline($tmp, $this->image_dst_x - $i -1, $this->image_dst_y - $i, $this->image_dst_x - $i -1, $i, $c2);
                            imageline($tmp, $this->image_dst_x - $i -1, $this->image_dst_y - $i -1, $i, $this->image_dst_y - $i -1, $c2);
                            imageline($tmp, $i, $i, $i, $this->image_dst_y - $i -1, $c1);
                        }
                        $image_dst = $this->imagetransfer($tmp, $image_dst);
                    }
                    if ($this->image_watermark!='' && file_exists($this->image_watermark)) {
                        $this->log .= '- add watermark<br />';
                        $this->image_watermark_position = strtolower($this->image_watermark_position);
                        $watermark_info = getimagesize($this->image_watermark);
                        $watermark_type = (array_key_exists(2, $watermark_info) ? $watermark_info[2] : null); // 1 = GIF, 2 = JPG, 3 = PNG
                        $watermark_checked = false;
                        if ($watermark_type == IMAGETYPE_GIF) {
                            if (!function_exists('imagecreatefromgif')) {
                                $this->error = $this->translate('watermark_no_create_support', array('GIF'));
                            } else {
                                $filter = @imagecreatefromgif($this->image_watermark);
                                if (!$filter) {
                                    $this->error = $this->translate('watermark_create_error', array('GIF'));
                                } else {
                                    $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;watermark source image is GIF<br />';
                                    $watermark_checked = true;
                                }
                            }
                        } else if ($watermark_type == IMAGETYPE_JPEG) {
                            if (!function_exists('imagecreatefromjpeg')) {
                                $this->error = $this->translate('watermark_no_create_support', array('JPEG'));
                            } else {
                                $filter = @imagecreatefromjpeg($this->image_watermark);
                                if (!$filter) {
                                    $this->error = $this->translate('watermark_create_error', array('JPEG'));
                                } else {
                                    $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;watermark source image is JPEG<br />';
                                    $watermark_checked = true;
                                }
                            }
                        } else if ($watermark_type == IMAGETYPE_PNG) {
                            if (!function_exists('imagecreatefrompng')) {
                                $this->error = $this->translate('watermark_no_create_support', array('PNG'));
                            } else {
                                $filter = @imagecreatefrompng($this->image_watermark);
                                if (!$filter) {
                                    $this->error = $this->translate('watermark_create_error', array('PNG'));
                                } else {
                                    $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;watermark source image is PNG<br />';
                                    $watermark_checked = true;
                                }
                            }
                        } else if ($watermark_type == IMAGETYPE_BMP) {
                            if (!method_exists($this, 'imagecreatefrombmp')) {
                                $this->error = $this->translate('watermark_no_create_support', array('BMP'));
                            } else {
                                $filter = @$this->imagecreatefrombmp($this->image_watermark);
                                if (!$filter) {
                                    $this->error = $this->translate('watermark_create_error', array('BMP'));
                                } else {
                                    $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;watermark source image is BMP<br />';
                                    $watermark_checked = true;
                                }
                            }
                        } else {
                            $this->error = $this->translate('watermark_invalid');
                        }
                        if ($watermark_checked) {
                            $watermark_width  = imagesx($filter);
                            $watermark_height = imagesy($filter);
                            $watermark_x = 0;
                            $watermark_y = 0;
                            if (is_numeric($this->image_watermark_x)) {
                                if ($this->image_watermark_x < 0) {
                                    $watermark_x = $this->image_dst_x - $watermark_width + $this->image_watermark_x;
                                } else {
                                    $watermark_x = $this->image_watermark_x;
                                }
                            } else {
                                if (strpos($this->image_watermark_position, 'r') !== false) {
                                    $watermark_x = $this->image_dst_x - $watermark_width;
                                } else if (strpos($this->image_watermark_position, 'l') !== false) {
                                    $watermark_x = 0;
                                } else {
                                    $watermark_x = ($this->image_dst_x - $watermark_width) / 2;
                                }
                            }
                            if (is_numeric($this->image_watermark_y)) {
                                if ($this->image_watermark_y < 0) {
                                    $watermark_y = $this->image_dst_y - $watermark_height + $this->image_watermark_y;
                                } else {
                                    $watermark_y = $this->image_watermark_y;
                                }
                            } else {
                                if (strpos($this->image_watermark_position, 'b') !== false) {
                                    $watermark_y = $this->image_dst_y - $watermark_height;
                                } else if (strpos($this->image_watermark_position, 't') !== false) {
                                    $watermark_y = 0;
                                } else {
                                    $watermark_y = ($this->image_dst_y - $watermark_height) / 2;
                                }
                            }
                            imagecopyresampled ($image_dst, $filter, $watermark_x, $watermark_y, 0, 0, $watermark_width, $watermark_height, $watermark_width, $watermark_height);
                        } else {
                            $this->error = $this->translate('watermark_invalid');
                        }
                    }
                    if (!empty($this->image_text)) {
                        $this->log .= '- add text<br />';
                        $src_size       = $this->file_src_size / 1024;
                        $src_size_mb    = number_format($src_size / 1024, 1, ".", " ");
                        $src_size_kb    = number_format($src_size, 1, ".", " ");
                        $src_size_human = ($src_size > 1024 ? $src_size_mb . " MB" : $src_size_kb . " kb");
                        $this->image_text = str_replace(
                            array('[src_name]',
                                  '[src_name_body]',
                                  '[src_name_ext]',
                                  '[src_pathname]',
                                  '[src_mime]',
                                  '[src_size]',
                                  '[src_size_kb]',
                                  '[src_size_mb]',
                                  '[src_size_human]',
                                  '[src_x]',
                                  '[src_y]',
                                  '[src_pixels]',
                                  '[src_type]',
                                  '[src_bits]',
                                  '[dst_path]',
                                  '[dst_name_body]',
                                  '[dst_name_ext]',
                                  '[dst_name]',
                                  '[dst_pathname]',
                                  '[dst_x]',
                                  '[dst_y]',
                                  '[date]',
                                  '[time]',
                                  '[host]',
                                  '[server]',
                                  '[ip]',
                                  '[gd_version]'),
                            array($this->file_src_name,
                                  $this->file_src_name_body,
                                  $this->file_src_name_ext,
                                  $this->file_src_pathname,
                                  $this->file_src_mime,
                                  $this->file_src_size,
                                  $src_size_kb,
                                  $src_size_mb,
                                  $src_size_human,
                                  $this->image_src_x,
                                  $this->image_src_y,
                                  $this->image_src_pixels,
                                  $this->image_src_type,
                                  $this->image_src_bits,
                                  $this->file_dst_path,
                                  $this->file_dst_name_body,
                                  $this->file_dst_name_ext,
                                  $this->file_dst_name,
                                  $this->file_dst_pathname,
                                  $this->image_dst_x,
                                  $this->image_dst_y,
                                  date('Y-m-d'),
                                  date('H:i:s'),
                                  (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'n/a'),
                                  (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'n/a'),
                                  (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'n/a'),
                                  $this->gdversion(true)),
                            $this->image_text);
                        if (!is_numeric($this->image_text_padding)) $this->image_text_padding = 0;
                        if (!is_numeric($this->image_text_line_spacing)) $this->image_text_line_spacing = 0;
                        if (!is_numeric($this->image_text_padding_x)) $this->image_text_padding_x = $this->image_text_padding;
                        if (!is_numeric($this->image_text_padding_y)) $this->image_text_padding_y = $this->image_text_padding;
                        $this->image_text_position = strtolower($this->image_text_position);
                        $this->image_text_direction = strtolower($this->image_text_direction);
                        $this->image_text_alignment = strtolower($this->image_text_alignment);
                        if (!is_numeric($this->image_text_font) && strlen($this->image_text_font) > 4 && substr(strtolower($this->image_text_font), -4) == '.gdf') {
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;try to load font ' . $this->image_text_font . '... ';
                            if ($this->image_text_font = @imageloadfont($this->image_text_font)) {
                                $this->log .=  'success<br />';
                            } else {
                                $this->log .=  'error<br />';
                                $this->image_text_font = 5;
                            }
                        }
                        $text = explode("\n", $this->image_text);
                        $char_width = imagefontwidth($this->image_text_font);
                        $char_height = imagefontheight($this->image_text_font);
                        $text_height = 0;
                        $text_width = 0;
                        $line_height = 0;
                        $line_width = 0;
                        foreach ($text as $k => $v) {
                            if ($this->image_text_direction == 'v') {
                                $h = ($char_width * strlen($v));
                                if ($h > $text_height) $text_height = $h;
                                $line_width = $char_height;
                                $text_width += $line_width + ($k < (sizeof($text)-1) ? $this->image_text_line_spacing : 0);
                            } else {
                                $w = ($char_width * strlen($v));
                                if ($w > $text_width) $text_width = $w;
                                $line_height = $char_height;
                                $text_height += $line_height + ($k < (sizeof($text)-1) ? $this->image_text_line_spacing : 0);
                            }
                        }
                        $text_width  += (2 * $this->image_text_padding_x);
                        $text_height += (2 * $this->image_text_padding_y);
                        $text_x = 0;
                        $text_y = 0;
                        if (is_numeric($this->image_text_x)) {
                            if ($this->image_text_x < 0) {
                                $text_x = $this->image_dst_x - $text_width + $this->image_text_x;
                            } else {
                                $text_x = $this->image_text_x;
                            }
                        } else {
                            if (strpos($this->image_text_position, 'r') !== false) {
                                $text_x = $this->image_dst_x - $text_width;
                            } else if (strpos($this->image_text_position, 'l') !== false) {
                                $text_x = 0;
                            } else {
                                $text_x = ($this->image_dst_x - $text_width) / 2;
                            }
                        }
                        if (is_numeric($this->image_text_y)) {
                            if ($this->image_text_y < 0) {
                                $text_y = $this->image_dst_y - $text_height + $this->image_text_y;
                            } else {
                                $text_y = $this->image_text_y;
                            }
                        } else {
                            if (strpos($this->image_text_position, 'b') !== false) {
                                $text_y = $this->image_dst_y - $text_height;
                            } else if (strpos($this->image_text_position, 't') !== false) {
                                $text_y = 0;
                            } else {
                                $text_y = ($this->image_dst_y - $text_height) / 2;
                            }
                        }
                        if (!empty($this->image_text_background)) {
                            list($red, $green, $blue) = $this->getcolors($this->image_text_background);
                            if ($gd_version >= 2 && (is_numeric($this->image_text_background_percent)) && $this->image_text_background_percent >= 0 && $this->image_text_background_percent <= 100) {
                                $filter = imagecreatetruecolor($text_width, $text_height);
                                $background_color = imagecolorallocate($filter, $red, $green, $blue);
                                imagefilledrectangle($filter, 0, 0, $text_width, $text_height, $background_color);
                                $this->imagecopymergealpha($image_dst, $filter, $text_x, $text_y, 0, 0, $text_width, $text_height, $this->image_text_background_percent);
                                imagedestroy($filter);
                            } else {
                                $background_color = imagecolorallocate($image_dst ,$red, $green, $blue);
                                imagefilledrectangle($image_dst, $text_x, $text_y, $text_x + $text_width, $text_y + $text_height, $background_color);
                            }
                        }
                        $text_x += $this->image_text_padding_x;
                        $text_y += $this->image_text_padding_y;
                        $t_width = $text_width - (2 * $this->image_text_padding_x);
                        $t_height = $text_height - (2 * $this->image_text_padding_y);
                        list($red, $green, $blue) = $this->getcolors($this->image_text_color);
                        if ($gd_version >= 2 && (is_numeric($this->image_text_percent)) && $this->image_text_percent >= 0 && $this->image_text_percent <= 100) {
                            if ($t_width < 0) $t_width = 0;
                            if ($t_height < 0) $t_height = 0;
                            $filter = $this->imagecreatenew($t_width, $t_height, false, true);
                            $text_color = imagecolorallocate($filter ,$red, $green, $blue);
                            foreach ($text as $k => $v) {
                                if ($this->image_text_direction == 'v') {
                                    imagestringup($filter,
                                                  $this->image_text_font,
                                                  $k * ($line_width  + ($k > 0 && $k < (sizeof($text)) ? $this->image_text_line_spacing : 0)),
                                                  $text_height - (2 * $this->image_text_padding_y) - ($this->image_text_alignment == 'l' ? 0 : (($t_height - strlen($v) * $char_width) / ($this->image_text_alignment == 'r' ? 1 : 2))) ,
                                                  $v,
                                                  $text_color);
                                } else {
                                    imagestring($filter,
                                                $this->image_text_font,
                                                ($this->image_text_alignment == 'l' ? 0 : (($t_width - strlen($v) * $char_width) / ($this->image_text_alignment == 'r' ? 1 : 2))),
                                                $k * ($line_height  + ($k > 0 && $k < (sizeof($text)) ? $this->image_text_line_spacing : 0)),
                                                $v,
                                                $text_color);
                                }
                            }
                            $this->imagecopymergealpha($image_dst, $filter, $text_x, $text_y, 0, 0, $t_width, $t_height, $this->image_text_percent);
                            imagedestroy($filter);

                        } else {
                            $text_color = imageColorAllocate($image_dst ,$red, $green, $blue);
                            foreach ($text as $k => $v) {
                                if ($this->image_text_direction == 'v') {
                                    imagestringup($image_dst,
                                                  $this->image_text_font,
                                                  $text_x + $k * ($line_width  + ($k > 0 && $k < (sizeof($text)) ? $this->image_text_line_spacing : 0)),
                                                  $text_y + $text_height - (2 * $this->image_text_padding_y) - ($this->image_text_alignment == 'l' ? 0 : (($t_height - strlen($v) * $char_width) / ($this->image_text_alignment == 'r' ? 1 : 2))),
                                                  $v,
                                                  $text_color);
                                } else {
                                    imagestring($image_dst,
                                                $this->image_text_font,
                                                $text_x + ($this->image_text_alignment == 'l' ? 0 : (($t_width - strlen($v) * $char_width) / ($this->image_text_alignment == 'r' ? 1 : 2))),
                                                $text_y + $k * ($line_height  + ($k > 0 && $k < (sizeof($text)) ? $this->image_text_line_spacing : 0)),
                                                $v,
                                                $text_color);
                                }
                            }
                        }
                    }
                    if ($this->image_reflection_height) {
                        $this->log .= '- add reflection : ' . $this->image_reflection_height . '<br />';
                        $image_reflection_height = $this->image_reflection_height;
                        if (strpos($image_reflection_height, '%')>0) $image_reflection_height = $this->image_dst_y * (str_replace('%','',$image_reflection_height / 100));
                        if (strpos($image_reflection_height, 'px')>0) $image_reflection_height = str_replace('px','',$image_reflection_height);
                        $image_reflection_height = (int) $image_reflection_height;
                        if ($image_reflection_height > $this->image_dst_y) $image_reflection_height = $this->image_dst_y;
                        if (empty($this->image_reflection_opacity)) $this->image_reflection_opacity = 60;
                        $tmp = $this->imagecreatenew($this->image_dst_x, $this->image_dst_y + $image_reflection_height + $this->image_reflection_space, true);
                        $transparency = $this->image_reflection_opacity;
                        imagecopy($tmp, $image_dst, 0, 0, 0, 0, $this->image_dst_x, $this->image_dst_y + ($this->image_reflection_space < 0 ? $this->image_reflection_space : 0));
                        if ($image_reflection_height + $this->image_reflection_space > 0) {
                            if (!empty($this->image_background_color)) {
                                list($red, $green, $blue) = $this->getcolors($this->image_background_color);
                                $fill = imagecolorallocate($tmp, $red, $green, $blue);
                            } else {
                                $fill = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
                            }
                            imagefill($tmp, round($this->image_dst_x / 2), $this->image_dst_y + $image_reflection_height + $this->image_reflection_space - 1, $fill);
                        }
                        for ($y = 0; $y < $image_reflection_height; $y++) {
                            for ($x = 0; $x < $this->image_dst_x; $x++) {
                                $pixel_b = imagecolorsforindex($tmp, imagecolorat($tmp, $x, $y + $this->image_dst_y + $this->image_reflection_space));
                                $pixel_o = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $this->image_dst_y - $y - 1 + ($this->image_reflection_space < 0 ? $this->image_reflection_space : 0)));
                                $alpha_o = 1 - ($pixel_o['alpha'] / 127);
                                $alpha_b = 1 - ($pixel_b['alpha'] / 127);
                                $opacity = $alpha_o * $transparency / 100;
                                if ($opacity > 0) {
                                    $red   = round((($pixel_o['red']   * $opacity) + ($pixel_b['red']  ) * $alpha_b) / ($alpha_b + $opacity));
                                    $green = round((($pixel_o['green'] * $opacity) + ($pixel_b['green']) * $alpha_b) / ($alpha_b + $opacity));
                                    $blue  = round((($pixel_o['blue']  * $opacity) + ($pixel_b['blue'] ) * $alpha_b) / ($alpha_b + $opacity));
                                    $alpha = ($opacity + $alpha_b);
                                    if ($alpha > 1) $alpha = 1;
                                    $alpha =  round((1 - $alpha) * 127);
                                    $color = imagecolorallocatealpha($tmp, $red, $green, $blue, $alpha);
                                    imagesetpixel($tmp, $x, $y + $this->image_dst_y + $this->image_reflection_space, $color);
                                }
                            }
                            if ($transparency > 0) $transparency = $transparency - ($this->image_reflection_opacity / $image_reflection_height);
                        }
                        $this->image_dst_y = $this->image_dst_y + $image_reflection_height + $this->image_reflection_space;
                        $image_dst = $this->imagetransfer($tmp, $image_dst);
                    }
                    if (is_numeric($this->jpeg_size) && $this->jpeg_size > 0 && ($this->image_convert == 'jpeg' || $this->image_convert == 'jpg')) {
                        $this->log .= '- JPEG desired file size : ' . $this->jpeg_size . '<br />';
                        ob_start(); imagejpeg($image_dst,'',75);  $buffer = ob_get_contents(); ob_end_clean();
                        $size75 = strlen($buffer);
                        ob_start(); imagejpeg($image_dst,'',50);  $buffer = ob_get_contents(); ob_end_clean();
                        $size50 = strlen($buffer);
                        ob_start(); imagejpeg($image_dst,'',25);  $buffer = ob_get_contents(); ob_end_clean();
                        $size25 = strlen($buffer);
                        $mgrad1 = 25 / ($size50-$size25);
                        $mgrad2 = 25 / ($size75-$size50);
                        $mgrad3 = 50 / ($size75-$size25);
                        $mgrad  = ($mgrad1 + $mgrad2 + $mgrad3) / 3;
                        $q_factor = round($mgrad * ($this->jpeg_size - $size50) + 50);
                        if ($q_factor<1) {
                            $this->jpeg_quality=1;
                        } elseif ($q_factor>100) {
                            $this->jpeg_quality=100;
                        } else {
                            $this->jpeg_quality=$q_factor;
                        }
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;JPEG quality factor set to ' . $this->jpeg_quality . '<br />';
                    }
                    $this->log .= '- converting...<br />';
                    switch($this->image_convert) {
                        case 'gif':
                            if (imageistruecolor($image_dst)) {
                                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;true color to palette<br />';
                                $mask = array(array());
                                for ($x = 0; $x < $this->image_dst_x; $x++) {
                                    for ($y = 0; $y < $this->image_dst_y; $y++) {
                                        $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                        $mask[$x][$y] = $pixel['alpha'];
                                    }
                                }
                                list($red, $green, $blue) = $this->getcolors($this->image_default_color);
                                for ($x = 0; $x < $this->image_dst_x; $x++) {
                                    for ($y = 0; $y < $this->image_dst_y; $y++) {
                                        if ($mask[$x][$y] > 0){
                                            $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                            $alpha = ($mask[$x][$y] / 127);
                                            $pixel['red'] = round(($pixel['red'] * (1 -$alpha) + $red * ($alpha)));
                                            $pixel['green'] = round(($pixel['green'] * (1 -$alpha) + $green * ($alpha)));
                                            $pixel['blue'] = round(($pixel['blue'] * (1 -$alpha) + $blue * ($alpha)));
                                            $color = imagecolorallocate($image_dst, $pixel['red'], $pixel['green'], $pixel['blue']);
                                            imagesetpixel($image_dst, $x, $y, $color);
                                        }
                                    }
                                }
                                if (empty($this->image_background_color)) {
                                    imagetruecolortopalette($image_dst, true, 255);
                                    $transparency = imagecolorallocate($image_dst, 254, 1, 253);
                                    imagecolortransparent($image_dst, $transparency);
                                    for ($x = 0; $x < $this->image_dst_x; $x++) {
                                        for ($y = 0; $y < $this->image_dst_y; $y++) {
                                            if ($mask[$x][$y] > 120) imagesetpixel($image_dst, $x, $y, $transparency);
                                        }
                                    }
                                }
                                unset($mask);
                            }
                            break;
                        case 'jpg':
                        case 'bmp':
                            $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;fills in transparency with default color<br />';
                            list($red, $green, $blue) = $this->getcolors($this->image_default_color);
                            $transparency = imagecolorallocate($image_dst, $red, $green, $blue);
                            for ($x = 0; $x < $this->image_dst_x; $x++) {
                                for ($y = 0; $y < $this->image_dst_y; $y++) {
                                    if (imageistruecolor($image_dst)) {
                                        $rgba = imagecolorat($image_dst, $x, $y);
                                        $pixel = array('red' => ($rgba >> 16) & 0xFF,
                                                       'green' => ($rgba >> 8) & 0xFF,
                                                       'blue' => $rgba & 0xFF,
                                                       'alpha' => ($rgba & 0x7F000000) >> 24);
                                    } else {
                                        $pixel = imagecolorsforindex($image_dst, imagecolorat($image_dst, $x, $y));
                                    }
                                    if ($pixel['alpha'] == 127) {
                                        imagesetpixel($image_dst, $x, $y, $transparency);
                                    } else if ($pixel['alpha'] > 0) {
                                        $alpha = ($pixel['alpha'] / 127);
                                        $pixel['red'] = round(($pixel['red'] * (1 -$alpha) + $red * ($alpha)));
                                        $pixel['green'] = round(($pixel['green'] * (1 -$alpha) + $green * ($alpha)));
                                        $pixel['blue'] = round(($pixel['blue'] * (1 -$alpha) + $blue * ($alpha)));
                                        $color = imagecolorclosest($image_dst, $pixel['red'], $pixel['green'], $pixel['blue']);
                                        imagesetpixel($image_dst, $x, $y, $color);
                                    }
                                }
                            }
                            break;
                        default:
                            break;
                    }
                    $this->log .= '- saving image...<br />';
                    switch($this->image_convert) {
                        case 'jpeg':
                        case 'jpg':
                            if (!$return_mode) {
                                $result = @imagejpeg($image_dst, $this->file_dst_pathname, $this->jpeg_quality);
                            } else {
                                ob_start();
                                $result = @imagejpeg($image_dst, '', $this->jpeg_quality);
                                $return_content = ob_get_contents();
                                ob_end_clean();
                            }
                            if (!$result) {
                                $this->processed = false;
                                $this->error = $this->translate('file_create', array('JPEG'));
                            } else {
                                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;JPEG image created<br />';
                            }
                            break;
                        case 'png':
                            imagealphablending( $image_dst, false );
                            imagesavealpha( $image_dst, true );
                            if (!$return_mode) {
                                $result = @imagepng($image_dst, $this->file_dst_pathname);
                            } else {
                                ob_start();
                                $result = @imagepng($image_dst);
                                $return_content = ob_get_contents();
                                ob_end_clean();
                            }
                            if (!$result) {
                                $this->processed = false;
                                $this->error = $this->translate('file_create', array('PNG'));
                            } else {
                                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;PNG image created<br />';
                            }
                            break;
                        case 'gif':
                            if (!$return_mode) {
                                $result = @imagegif($image_dst, $this->file_dst_pathname);
                            } else {
                                ob_start();
                                $result = @imagegif($image_dst);
                                $return_content = ob_get_contents();
                                ob_end_clean();
                            }
                            if (!$result) {
                                $this->processed = false;
                                $this->error = $this->translate('file_create', array('GIF'));
                            } else {
                                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;GIF image created<br />';
                            }
                            break;
                        case 'bmp':
                            if (!$return_mode) {
                                $result = $this->imagebmp($image_dst, $this->file_dst_pathname);
                            } else {
                                ob_start();
                                $result = $this->imagebmp($image_dst);
                                $return_content = ob_get_contents();
                                ob_end_clean();
                            }
                            if (!$result) {
                                $this->processed = false;
                                $this->error = $this->translate('file_create', array('BMP'));
                            } else {
                                $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;BMP image created<br />';
                            }
                            break;
                        default:
                            $this->processed = false;
                            $this->error = $this->translate('no_conversion_type');
                    }
                    if ($this->processed) {
                        if (is_resource($image_src)) imagedestroy($image_src);
                        if (is_resource($image_dst)) imagedestroy($image_dst);
                        $this->log .= '&nbsp;&nbsp;&nbsp;&nbsp;image objects destroyed<br />';
                    }
                }
            } else {
                $this->log .= '- no image processing wanted<br />';

                if (!$return_mode) {
                    if (!copy($this->file_src_pathname, $this->file_dst_pathname)) {
                        $this->processed = false;
                        $this->error = $this->translate('copy_failed');
                    }
                } else {
                    $return_content = @file_get_contents($this->file_src_pathname);
                    if ($return_content === FALSE) {
                        $this->processed = false;
                        $this->error = $this->translate('reading_failed');
                    }
                }
            }
        }
        if ($this->processed) {
            $this->log .= '- <b>process OK</b><br />';
        } else {
            $this->log .= '- <b>error</b>: ' . $this->error . '<br />';
        }
        $this->init();
        if ($return_mode) return $return_content;
    }

    function clean() {
        $this->log .= '<b>cleanup</b><br />';
        $this->log .= '- delete temp file '  . $this->file_src_pathname . '<br />';
        @unlink($this->file_src_pathname);
    }

    function imagecreatefrombmp($filename) {
        if (! $f1 = fopen($filename,"rb")) return false;

        $file = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
        if ($file['file_type'] != 19778) return false;

        $bmp = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
                      '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
                      '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
        $bmp['colors'] = pow(2,$bmp['bits_per_pixel']);
        if ($bmp['size_bitmap'] == 0) $bmp['size_bitmap'] = $file['file_size'] - $file['bitmap_offset'];
        $bmp['bytes_per_pixel'] = $bmp['bits_per_pixel']/8;
        $bmp['bytes_per_pixel2'] = ceil($bmp['bytes_per_pixel']);
        $bmp['decal'] = ($bmp['width']*$bmp['bytes_per_pixel']/4);
        $bmp['decal'] -= floor($bmp['width']*$bmp['bytes_per_pixel']/4);
        $bmp['decal'] = 4-(4*$bmp['decal']);
        if ($bmp['decal'] == 4) $bmp['decal'] = 0;
        $palette = array();
        if ($bmp['colors'] < 16777216) {
            $palette = unpack('V'.$bmp['colors'], fread($f1,$bmp['colors']*4));
        }
        $im = fread($f1,$bmp['size_bitmap']);
        $vide = chr(0);
        $res = imagecreatetruecolor($bmp['width'],$bmp['height']);
        $P = 0;
        $Y = $bmp['height']-1;
        while ($Y >= 0) {
            $X=0;
            while ($X < $bmp['width']) {
                if ($bmp['bits_per_pixel'] == 24)
                    $color = unpack("V",substr($im,$P,3).$vide);
                elseif ($bmp['bits_per_pixel'] == 16) {
                    $color = unpack("n",substr($im,$P,2));
                    $color[1] = $palette[$color[1]+1];
                } elseif ($bmp['bits_per_pixel'] == 8) {
                    $color = unpack("n",$vide.substr($im,$P,1));
                    $color[1] = $palette[$color[1]+1];
                } elseif ($bmp['bits_per_pixel'] == 4) {
                    $color = unpack("n",$vide.substr($im,floor($P),1));
                    if (($P*2)%2 == 0) $color[1] = ($color[1] >> 4) ; else $color[1] = ($color[1] & 0x0F);
                    $color[1] = $palette[$color[1]+1];
                } elseif ($bmp['bits_per_pixel'] == 1)  {
                    $color = unpack("n",$vide.substr($im,floor($P),1));
                    if     (($P*8)%8 == 0) $color[1] =  $color[1]        >>7;
                    elseif (($P*8)%8 == 1) $color[1] = ($color[1] & 0x40)>>6;
                    elseif (($P*8)%8 == 2) $color[1] = ($color[1] & 0x20)>>5;
                    elseif (($P*8)%8 == 3) $color[1] = ($color[1] & 0x10)>>4;
                    elseif (($P*8)%8 == 4) $color[1] = ($color[1] & 0x8)>>3;
                    elseif (($P*8)%8 == 5) $color[1] = ($color[1] & 0x4)>>2;
                    elseif (($P*8)%8 == 6) $color[1] = ($color[1] & 0x2)>>1;
                    elseif (($P*8)%8 == 7) $color[1] = ($color[1] & 0x1);
                    $color[1] = $palette[$color[1]+1];
                } else
                    return FALSE;
                imagesetpixel($res,$X,$Y,$color[1]);
                $X++;
                $P += $bmp['bytes_per_pixel'];
            }
            $Y--;
            $P+=$bmp['decal'];
        }
        fclose($f1);
        return $res;
    }

    function imagebmp(&$im, $filename = "") {
        if (!$im) return false;
        $w = imagesx($im);
        $h = imagesy($im);
        $result = '';
        if (!imageistruecolor($im)) {
            $tmp = imagecreatetruecolor($w, $h);
            imagecopy($tmp, $im, 0, 0, 0, 0, $w, $h);
            imagedestroy($im);
            $im = & $tmp;
        }
        $biBPLine = $w * 3;
        $biStride = ($biBPLine + 3) & ~3;
        $biSizeImage = $biStride * $h;
        $bfOffBits = 54;
        $bfSize = $bfOffBits + $biSizeImage;
        $result .= substr('BM', 0, 2);
        $result .=  pack ('VvvV', $bfSize, 0, 0, $bfOffBits);
        $result .= pack ('VVVvvVVVVVV', 40, $w, $h, 1, 24, 0, $biSizeImage, 0, 0, 0, 0);
        $numpad = $biStride - $biBPLine;
        for ($y = $h - 1; $y >= 0; --$y) {
            for ($x = 0; $x < $w; ++$x) {
                $col = imagecolorat ($im, $x, $y);
                $result .=  substr(pack ('V', $col), 0, 3);
            }
            for ($i = 0; $i < $numpad; ++$i)
                $result .= pack ('C', 0);
        }
        if($filename==""){
            echo $result;
        } else {
            $file = fopen($filename, "wb");
            fwrite($file, $result);
            fclose($file);
        }
        return true;
    }
}

?>