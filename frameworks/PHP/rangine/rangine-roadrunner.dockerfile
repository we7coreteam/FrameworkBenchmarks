FROM ccr.ccs.tencentyun.com/w7team/swoole:fpm

ADD ./ /rangine
WORKDIR /rangine

RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
RUN composer clearcache
RUN composer install --optimize-autoloader --classmap-authoritative --no-dev --quiet \
	&& chmod -R 777 /rangine && chmod -R 777 /usr/tmp

RUN composer update \
    && ./vendor/bin/rr get-binary

CMD bin/rr serve -v