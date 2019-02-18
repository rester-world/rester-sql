FROM rester/rester-docker
MAINTAINER Woong-Gi Jeon<jeon.wbbi@gmail.com>

RUN mkdir /var/www/cfg

ADD cfg /var/www/cfg
ADD src /var/www/html
ADD default.conf /etc/nginx/sites-available/default.conf

VOLUME ["/var/www/cfg"]
VOLUME ["/var/www/html/modules"]

