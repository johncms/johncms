<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class Counters
{
    /**
     * @var \PDO
     */
    private $db;

    private $homeurl;

    public function __invoke(ContainerInterface $container)
    {
        $this->db = $container->get(\PDO::class);
        $this->homeurl = $container->get('config')['johncms']['homeurl'];

        return $this;
    }

    /**
     * Счетчик Фотоальбомов пользователей
     *
     * @return string
     */
    public function album()
    {
        $file = ROOTPATH . 'files/cache/count_album.dat';
        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $album = $res['album'];
            $photo = $res['photo'];
            $new = $res['new'];
            $new_adm = $res['new_adm'];
        } else {
            $album = $this->db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`')->fetchColumn();
            $photo = $this->db->query('SELECT COUNT(*) FROM `cms_album_files`')->fetchColumn();
            $new = $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` = 4')->fetchColumn();
            $new_adm = $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` > 1')->fetchColumn();
            file_put_contents($file, serialize(['album' => $album, 'photo' => $photo, 'new' => $new, 'new_adm' => $new_adm]));
        }

        $newcount = 0;
        if (\core::$user_rights >= 6 && $new_adm) {
            $newcount = $new_adm;
        } elseif ($new) {
            $newcount = $new;
        }

        return $album . '&#160;/&#160;' . $photo .
        ($newcount ? '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/album/index.php?act=top">+' . $newcount . '</a></span>' : '');
    }

    /**
     * Счетчик загруз центра
     *
     * @return string
     */
    function downloads()
    {
        $file = ROOTPATH . 'files/cache/count_downloads.dat';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $total = isset($res['total']) ? $res['total'] : 0;
            $new = isset($res['new']) ? $res['new'] : 0;
            $mod = isset($res['mod']) ? $res['mod'] : 0;
        } else {
            $old = time() - (3 * 24 * 3600);
            $total = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'")->fetchColumn();
            $new = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `time` > '$old'")->fetchColumn();
            $mod = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn();

            file_put_contents($file, serialize(['total' => $total, 'new' => $new, 'mod' => $mod]));
        }

        if ($new > 0) {
            $total .= '&nbsp;/&nbsp;<span class="red"><a href="downloads/?act=new_files">+' . $new . '</a></span>';
        }

        if (\core::$user_rights == 4 || \core::$user_rights >= 6) {
            if ($mod) {
                $total .= '&nbsp;/&nbsp;<span class="red"><a href="downloads/?act=mod_files">м. ' . $mod . '</a></span>';
            }
        }

        return $total;
    }
}
