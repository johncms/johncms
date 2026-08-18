# [JohnCMS](https://johncms.com)

[![GitHub](https://img.shields.io/github/license/johncms/johncms?color=blue)](https://github.com/johncms/johncms/blob/develop/LICENSE)
[![Source Code](http://img.shields.io/badge/source-johncms/johncms-blue.svg)](https://github.com/johncms/johncms)
[![GitHub tag (latest SemVer)](https://img.shields.io/github/tag/johncms/johncms.svg?label=stable)](https://github.com/johncms/johncms/releases)

[![PHP-CI](https://github.com/johncms/johncms/workflows/PHP-CI/badge.svg?branch=9.x)](https://github.com/johncms/johncms/actions)
[![Crowdin](https://badges.crowdin.net/johncms/localized.svg)](https://crowdin.com/project/johncms)

JohnCMS is an open-source PHP community CMS. It includes a forum, news, library, downloads, private messages, photo albums, and more — all in one package.

## Features

- **Forum** — CKEditor-based editor with image upload, SEO-friendly URLs for sections and topics, polls, file attachments, topic pinning and closing
- **News** — unlimited nested sections, scheduled publishing, tags, comments, ratings
- **Library** — unlimited nested sections, user-submitted articles, moderation
- **Downloads** — file catalog with ratings and comments
- **Private messages** — with file attachments
- **Photo albums** — personal user albums
- **Guestbook** — site-wide and personal guestbooks
- **Notifications** — notification center with per-user settings
- **Dark theme** — built-in light/dark theme switch
- **Multilingual** — full i18n support via Crowdin
- **Sitemap** — automatic XML sitemap generation

## Requirements

- PHP **8.4** or higher
- MySQL **5.6.4** or higher (MySQL Native Driver `mysqlnd` required)
- Apache with `.htaccess` support
- PHP extensions: `imagick` or `gd`, `mbstring`, `pdo`, `simplexml`

## Installation from distribution

1. Download the latest release archive from the [releases page](https://github.com/johncms/johncms/releases).
2. Extract and upload the files to your server's web root.
3. Open `http://your.site/install` in a browser and follow the installer steps.
4. **Delete the `/install` directory** after installation is complete.

## Installation from repository

1. Make sure you have [Composer](https://getcomposer.org/) and [Node.js](https://nodejs.org/) installed.
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install Node.js dependencies and build the frontend:
   ```bash
   npm install
   npm run build
   ```
4. Open `http://your.site/install` in a browser and follow the installer steps.
5. **Delete the `/install` directory** after installation is complete.
6. Set up a cron job to run the scheduler every minute:
   ```
   * * * * * php /path/to/system/bin/console schedule:run --no-interaction
   ```

## Installation with Docker

1. Copy the environment file and adjust the values if needed:
   ```bash
   cp .env.example .env
   ```
2. Build and start the containers:
   ```bash
   make build
   make up
   ```
3. Install PHP dependencies:
   ```bash
   make composer-install
   ```
4. Build the frontend on the host (the containers do not include Node.js):
   ```bash
   npm install
   npm run build
   ```
5. Open `http://your.site/install` in a browser and follow the installer steps.
6. **Delete the `/install` directory** after installation is complete.

The Docker setup includes Nginx, PHP-FPM, MariaDB, and [Ofelia](https://github.com/mcuadros/ofelia) for running the scheduler automatically every minute.

### Useful make commands

| Command | Description |
|---|---|
| `make up` | Start containers |
| `make stop` | Stop containers |
| `make restart` | Restart containers |
| `make rebuild` | Rebuild and restart containers |
| `make shell` | Open shell inside the PHP-FPM container |
| `make composer-install` | Run `composer install` inside the container |
| `make composer-update` | Run `composer update` inside the container |
| `make backup-db` | Dump the database to a SQL file |
| `make restore-db` | Restore the database from a SQL file |

For detailed Docker configuration instructions see [.docker/readme.md](.docker/readme.md).

## Updating from repository

- If `composer.json` changed — re-run `composer install`.
- If `package.json` changed — re-run `npm install && npm run build`.
- If only `.scss`/`.js`/`.vue` files changed — re-run `npm run build`.
- During development, use `npm run dev` to start the Vite dev server with hot module replacement.

## Documentation

Full documentation is available at [docs.johncms.com](https://docs.johncms.com/).

## Contributing & Translations

Translations are managed on [Crowdin](https://crowdin.com/project/johncms). Feel free to contribute or report issues on [GitHub](https://github.com/johncms/johncms/issues).
