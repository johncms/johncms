# Changelog 
This project follows [semantic versioning](https://semver.org/).  
All notable changes to this project will be documented in this file.  
Detailed change can see in the [repository log](https://github.com/johncms/johncms/commits).

История версий 9.x и ранее — в [changelog ветки 9.x](https://github.com/johncms/johncms/blob/9.x/CHANGELOG.md).

## 10.0 - Unreleased

#### Breaking changes
- **Document root переехал в `public/`.** Теперь по HTTP доступен только этот каталог: `index.php`, `favicon.ico`, `robots.txt`, `sitemap*.xml`, `assets/`, `upload/`, `install/` и ассеты тем (`public/themes/<тема>/assets/`). Код, конфигурация и шаблоны остались в корне и больше не доступны из веба. **URL не изменились ни один.**

  При обновлении переведите document root сайта на `<каталог сайта>/public` (в nginx — директива `root`, в панели хостинга — «корневая директория сайта») и перезапустите php-fpm: кэш realpath держит старые пути. Если сменить document root нельзя, на Apache сработает `.htaccess` в корне: он перенаправит запросы в `public/` и закроет доступ к коду. На nginx такой запасной вариант невозможен — там смена root обязательна.

  Исходники тем (`themes/<тема>/src`, `templates`) остались в корне; собранные ассеты авторам тем нужно класть в `public/themes/<тема>/assets/`.
- Composer: зависимости переехали из `system/vendor` в стандартный `vendor/`. При обновлении удалите каталог `system/vendor` и выполните `composer install`.
- Из каталога `install/` удалены разовые скрипты обновления с версий ниже 9.9, конвертеры и скрипты доустановки модулей 9.9. Обновляйтесь по пути 9.8 → 9.9 → 10.0: скрипты и инструкции к ним остались в ветке `9.x`. Каталог `install/` теперь содержит только веб-инсталлятор.
