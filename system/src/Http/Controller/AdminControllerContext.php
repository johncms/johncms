<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;
use PDO;

final readonly class AdminControllerContext
{
    public function __construct(
        private Render $render,
        private Translator $translator,
        private PDO $db,
        private NavChain $navChain,
    ) {
    }

    public function initModule(string $moduleName = ''): void
    {
        if ($moduleName !== '') {
            $this->render->addFolder(
                $moduleName,
                MODULES_PATH . $moduleName . '/templates/'
            );

            $this->translator->addTranslationDomain(
                $moduleName,
                MODULES_PATH . $moduleName . '/locale'
            );
        }

        $this->translator->addTranslationDomain('admin', MODULES_PATH . 'admin/locale', false);

        $this->render->addData(
            [
                'regtotal' => $this->db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='0'")->fetchColumn(),
                'countusers' => $this->db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='1'")->fetchColumn(),
                'countadm' => $this->db->query("SELECT COUNT(*) FROM `users` WHERE `rights` >= '1'")->fetchColumn(),
                'bantotal' => $this->db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time` > '" . time() . "'")->fetchColumn(),
            ],
            [
                'system::app/sidebar-admin-menu',
            ]
        );

        $this->navChain->add(d__('admin', 'Admin Panel'), '/admin/');
    }
}
