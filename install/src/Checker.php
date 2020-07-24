<?php

class Checker
{
    public const MIN_PHP_VERSION = '7.2';

    protected $act;

    public function __construct($act)
    {
        $this->act = $act;

        if (!file_exists(Installer::getLangFile())) {
            die(Render::html('error', [
                'error_title' => 'Language file is missing',
                'error_message' => sprintf('Not found file by path "%s"', Installer::getLangFile()),
            ]));
        }

        Installer::loadLang();
    }

    /**
     * Render errors if their exists
     *
     * @param $reports
     */
    public function renderCheckAll($reports): void
    {
        $hasErrors = false;
        $data = [
            'error_title'   => 'Checker error',
            'error_message' => '',
        ];

        foreach ($reports as $report) {
            if ($report['error']) {
                $hasErrors = true;
                $data['error_message'] .= $report['data']['error_title'] . '<br>' . $report['data']['error_message'] . '<br><br>';
            }
        }

        if ($hasErrors) {
            die(Render::html('error', $data));
        }
    }

    /**
     * @return array [
     *      [n] => [
     *          'error' => 'bool',
     *          'data'  => 'array',
     *      ]
     * ]
     */
    public function checkAll(): array
    {
        $reports = [];

        array_push($reports, $this->checkVersion());
        array_push($reports, $this->checkIsInstall());
        array_push($reports, $this->checkPhpErrors());
        array_push($reports, $this->checkPhpWarnings());
        array_push($reports, $this->checkFolders());
        array_push($reports, $this->checkFiles());

        return $reports;
    }

    /**
     * @return array [
     *      'error' => 'bool',
     *      'data'  => 'array',
     * ]
     */
    public function checkVersion(): array
    {
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            return [
                'error' => true,
                'data' => [
                    'error_title' => 'Invalid php version',
                    'error_message' => sprintf('Minimum php version %s<br/>Your php version %s', MIN_PHP_VERSION, PHP_VERSION),
                ],
            ];
        }

        return ['error' => false, 'data'  => [],];
    }

    /**
     * @return array [
     *      'error' => 'bool',
     *      'data'  => 'array',
     * ]
     */
    public function checkIsInstall(): array
    {
        if ($this->act !== 'final' && Installer::checkIsInstall()) {
            $raw = '<ul><li>/config/autoload/<strong>database.local.php</strong></li><li>/config/autoload/<strong>system.local.php</strong></li></ul>';
            return [
                'error' => true,
                'data'  => [
                    'error_title' => Installer::$lang['already_installed'],
                    'error_message' => Installer::$lang['to_install_again'] . $raw,
                ],
            ];
        }

        return ['error' => false, 'data'  => [],];
    }

    /**
     * @return array [
     *      'error' => 'bool',
     *      'data'  => 'array',
     * ]
     */
    public function checkPhpErrors(): array
    {
        if (($php_errors = Installer::checkPhpErrors()) !== false) {
            return [
                'error' => true,
                'data' => [
                    'error_title' => Installer::$lang['php_critical_error'],
                    'error_message' => implode('<br>', $php_errors),
                ],
            ];
        }

        return ['error' => false, 'data'  => [],];
    }

    /**
     * @return array [
     *      'error' => 'bool',
     *      'data'  => 'array',
     * ]
     */
    public function checkPhpWarnings(): array
    {
        if (($php_warnings = Installer::checkPhpWarnings()) !== false) {
            return [
                'error' => true,
                'data' => [
                    'error_title' => Installer::$lang['php_warnings'],
                    'error_message' => implode('<br>', $php_warnings),
                ],
            ];
        }

        return ['error' => false, 'data'  => [],];
    }

    /**
     * @return array [
     *      'error' => 'bool',
     *      'data'  => 'array',
     * ]
     */
    public function checkFolders(): array
    {
        if (($folders = Installer::checkFoldersRights()) !== false) {
            return [
                'error' => true,
                'data'  => [
                    'error_title' => Installer::$lang['access_rights'],
                    'error_message' => implode('<br>', $folders),
                ],
            ];
        }

        return ['error' => false, 'data'  => [],];
    }

    /**
     * @return array [
     *      'error' => 'bool',
     *      'data'  => 'array',
     * ]
     */
    public function checkFiles(): array
    {
        if (($files = Installer::checkFilesRights()) !== false) {
            return [
                'error' => true,
                'data'  => [
                    'error_title' => Installer::$lang['access_rights'],
                    'error_message' => implode('<br>', $files),
                ],
            ];
        }

        return ['error' => false, 'data'  => [],];
    }
}
