FROM rester/rester-docker
MAINTAINER Woong-Gi Jeon<jeon.wbbi@gmail.com>
MAINTAINER Kevin Park<kevinpark@webace.co.kr>

RUN mkdir /var/www/cfg
RUN mkdir /var/www/rester-core
RUN mkdir /var/www/rester-sql
RUN mkdir /var/www/src
RUN mkdir /var/www/exten_lib

ADD sql/cfg /var/www/cfg
ADD rester-core /var/www/rester-core
ADD rester-sql /var/www/rester-sql
ADD sql/src /var/www/src
ADD nginx-conf/default.conf /etc/nginx/sites-available/default.conf
ADD nginx-conf/default-ssl.conf /etc/nginx/sites-available/default-ssl.conf
ADD /shell/start.sh /start.sh
RUN chmod 755 /start.sh

VOLUME ["/var/www/cfg"]
VOLUME ["/var/www/rester-core"]
VOLUME ["/var/www/rester-sql"]
VOLUME ["/var/www/src"]
VOLUME ["/var/www/exten_lib"]

