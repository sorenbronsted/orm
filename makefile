.PHONY: test
name = orm
test:
	vendor/bin/phpunit

coverage:
	export XDEBUG_MODE=coverage; vendor/bin/phpunit --coverage-text

coverage-report:
	export XDEBUG_MODE=coverage; vendor/bin/phpunit --coverage-html build

sample:
	php src/sample.php

build:
	docker build -t $(name) .

start:
	docker run -dt --name $(name) -p 8000:8000 -v $(shell pwd):/app $(name)

stop:
	docker stop $(name)
	docker rm $(name)

bash:
	docker exec -it $(name) bash

clean:
	docker rm $(shell docker ps -aq)

composer:
	docker run --rm --interactive --tty --volume ${PWD}:/app composer ${CMD}

