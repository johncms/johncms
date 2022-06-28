prepare:
	cp -R ./system/vendor/maximebf/debugbar/src/DebugBar/Resources/* themes/default/assets/debugbar

cms-install:
	composer install
	npm install
	npm run prod
	npm run prod-admin

cms-update:
	composer install
	npm install
	npm run prod
	npm run prod-admin
	php johncms migrate
	php johncms cache:clear
