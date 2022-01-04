
.PHONY: test
test:
	vendor/bin/phpunit

coverage:
	export XDEBUG_MODE=coverage; vendor/bin/phpunit --coverage-text

coverage-report:
	export XDEBUG_MODE=coverage; vendor/bin/phpunit --coverage-html build

serve:
	php -S localhost:8000 -t public

sample:
	php src/sample.php

