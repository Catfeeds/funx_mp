FROM registry.cn-beijing.aliyuncs.com/wa/php-fpm:1.0.4

MAINTAINER Chuanjian Wang <chuanjian@funxdata.com>

ENV APPLICATION_ENV=development

ADD application /var/www/html/application
ADD public /var/www/html/public
ADD system /var/www/html/system

ENV SKIP_COMPOSER=true
RUN cd /var/www/html/application ;\
    composer dump-autoload --optimize

ADD hack/nginx.conf /etc/nginx/nginx.conf
ADD hack/start.sh /start.sh

WORKDIR /

CMD ["/start.sh"]
