FROM rester/rester-docker
MAINTAINER Woong-Gi Jeon<jeon.wbbi@gmail.com>
MAINTAINER Woong-Gi Jeon<kevinpark@webace.co.kr>

RUN mkdir /var/www/cfg
RUN mkdir /var/www/rester-core
RUN mkdir /var/www/rester-sql
RUN mkdir /var/www/src

ADD cfg /var/www/cfg
ADD rester-core /var/www/rester-core
ADD rester-sql /var/www/rester-sql
ADD src /var/www/src
ADD nginx-conf/default.conf /etc/nginx/sites-available/default.conf
ADD nginx-conf/default-ssl.conf /etc/nginx/sites-available/default-ssl.conf

VOLUME ["/var/www/cfg"]
VOLUME ["/var/www/rester-core"]
VOLUME ["/var/www/rester-sql"]
VOLUME ["/var/www/src"]

