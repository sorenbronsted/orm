
name = orm
image:
	docker build -t $(name) .

bash:
	docker run -u 1000:1000 -it -v ${PWD}:/app $(name) bash

root-bash:
	docker run -it -v ${PWD}:/app $(name) bash

