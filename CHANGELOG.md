# Changelog 
This project follows [semantic versioning](https://semver.org/).  
All notable changes to this project will be documented in this file.  
Detailed change can see in the [repository log](https://github.com/johncms/johncms/commits).

История версий 9.x и ранее — в [changelog ветки 9.x](https://github.com/johncms/johncms/blob/9.x/CHANGELOG.md).

## 10.0 - Unreleased

#### Breaking changes
- Composer: зависимости переехали из `system/vendor` в стандартный `vendor/`. При обновлении удалите каталог `system/vendor` и выполните `composer install`.
- Из каталога `install/` удалены разовые скрипты обновления с версий ниже 9.9, конвертеры и скрипты доустановки модулей 9.9. Обновляйтесь по пути 9.8 → 9.9 → 10.0: скрипты и инструкции к ним остались в ветке `9.x`. Каталог `install/` теперь содержит только веб-инсталлятор.
