# [JohnCMS 10](https://johncms.com)

[![GitHub](https://img.shields.io/github/license/johncms/johncms?color=blue)](https://github.com/johncms/johncms/blob/develop/LICENSE)
[![Source Code](http://img.shields.io/badge/source-johncms/johncms-blue.svg)](https://github.com/johncms/johncms)
[![GitHub tag (latest SemVer)](https://img.shields.io/github/tag/johncms/johncms.svg?label=stable)](https://github.com/johncms/johncms/releases)

[![PHP-CI](https://github.com/johncms/johncms/workflows/PHP-CI/badge.svg?branch=develop)](https://github.com/johncms/johncms/actions)
[![Crowdin](https://badges.crowdin.net/johncms/localized.svg)](https://crowdin.com/project/johncms)

This version is at an early stage of development. Many things may not work or work not as intended.

## System requirements

- PHP 8.0 and higher
- MySQL 5.7
- PHP configured to use MySQL Native Driver (mysqlnd)
- .htaccess support

## Installation

**To install the general availability version**, go to the [**project website**](https://johncms.com/downloads) and download the latest available distributive.
Then follow the installation instructions that came with it.

**To install the developer version**, you must have a [Composer](https://getcomposer.org) dependency manager
and [GIT](https://git-scm.com/) version control system.
1. Clone or download this repository on local workstation.
2. Assign the repository folder as Apache virtual host, or move contents to the previously created virtual host folder.
3. Create MySQL Database.
4. Open the console in the virtual host folder and install the dependencies using the command
```bash
composer install
```
5. Open the url in your browser (replace your.site to your virtualhost name): http://your.site/install and follow the instructions in the installer.
6. **This is all done**. If you go to the address of your virtual host from the browser, you should see a working site with demo data.

## Problems and solutions

When you get updates from the repository, you need to follow the changes of some files.

- If composer.json has been changed, you need to run the command `composer install`.
- After updating or after changing routes, run the command `php johncms cache:clear`.
