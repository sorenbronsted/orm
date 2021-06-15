
.PHONY: test
test:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-text

coverage-report:
	vendor/bin/phpunit --coverage-html build

serve:
	php -S localhost:8000 -t public

sample:
	php src/sample.php

