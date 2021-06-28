# [JohnCMS 10](https://johncms.com)

[![GitHub](https://img.shields.io/github/license/johncms/johncms?color=blue)](https://github.com/johncms/johncms/blob/develop/LICENSE)
[![Source Code](http://img.shields.io/badge/source-johncms/johncms-blue.svg)](https://github.com/johncms/johncms)
[![GitHub tag (latest SemVer)](https://img.shields.io/github/tag/johncms/johncms.svg?label=stable)](https://github.com/johncms/johncms/releases)

[![PHP-CI](https://github.com/johncms/johncms/workflows/PHP-CI/badge.svg?branch=develop)](https://github.com/johncms/johncms/actions)
[![Crowdin](https://badges.crowdin.net/johncms/localized.svg)](https://crowdin.com/project/johncms)

This version is at an early stage of development. Many things may not work or work not as intended.

## System requirements

- PHP 7.4 and higher
- MySQL 5.7
- PHP configured to use MySQL Native Driver (mysqlnd)
- .htaccess support

## Installation from the repository

1. You need to install [Composer](https://getcomposer.org/) on your computer.
2. Clone or download and unpack the repository to the root directory your site.
2. Run the command to install dependencies

```bash
composer install
```

5. Open the url in your browser (replace your.site to your hostname): http://your.site/install and follow the instructions in the installer.

## Problems and solutions

When you get updates from the repository, you need to follow the changes of some files.

- If composer.json has been changed, you need to run the command `composer install`.
