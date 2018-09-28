SERVICE:=client-api
# Variables
PWD := $(shell pwd)
DEV_UI_IMAGE := registry.cn-beijing.aliyuncs.com/wa/php-fpm:1.0.12
IMG_HUB?=registry.cn-shenzhen.aliyuncs.com/funxdata
REDIS_IMG?=registry.cn-beijing.aliyuncs.com/wa/redis:3.2
# Version information
VERSION=${shell cat VERSION 2> /dev/null}

sync:
	rsync -vaz --delete \
	 --exclude=.git \
	 --exclude=production \
	 --exclude=logs \
	 --exclude=cache \
	 ./application/ pre:/data/wwwroot/fxpms_boss/funxpms_boss/application/

dev: redis
	docker run --rm -it \
	 --name $(SERVICE)-dev \
	 -p 80:80 \
	 -e APPLICATION_ENV=development \
	 -v $(PWD)/hack/nginx.conf:/etc/nginx/nginx.conf \
	 -v $(PWD):/var/www/html/ \
	 -w /var/www/html/ \
	 --link funx-redis:redis \
	 $(DEV_UI_IMAGE) bash

run: image
	docker run --rm -it \
	 --name ${SERVICE} \
	 -e APPLICATION_ENV=development \
	 -p 80:80 \
	 --link funx-redis:redis \
	 $(IMG_HUB)/$(SERVICE):latest

redis:
	-docker run -d \
	 --name funx-redis \
	 -p 6379:6379 \
	 ${REDIS_IMG}

image:
	docker build -t $(IMG_HUB)/$(SERVICE):latest .

push: image
	docker push $(IMG_HUB)/$(SERVICE):latest

image-prod:
	docker build -t $(IMG_HUB)/$(SERVICE):$(VERSION) .

push-prod: image-prod
	docker push $(IMG_HUB)/$(SERVICE):$(VERSION)
