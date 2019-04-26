FROM ubuntu:18.04

ARG DEBIAN_FRONTEND=noninteractive

RUN apt update -y && apt install -y wget curl git

# php
RUN apt -y install php-cli php-fpm

# pinba extension for php
RUN apt -y install php-pinba

# clickhouse
RUN echo "deb http://repo.yandex.ru/clickhouse/deb/stable/ main/" > /etc/apt/sources.list.d/clickhouse.list && \
    apt install -y dirmngr && apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv E0C56BD4 && \
    apt update && apt install -y clickhouse-client clickhouse-server

# grafana and clickhouse plugin
RUN apt install -y adduser libfontconfig1 && \
    wget https://dl.grafana.com/oss/release/grafana_6.1.4_amd64.deb && dpkg -i grafana_6.1.4_amd64.deb && \
    grafana-cli plugins install vertamedia-clickhouse-datasource

# nginx
RUN apt install -y nginx

# pinba-server
RUN cd /var/www/ && git clone https://github.com/pinba-server/pinba-server.git && cd pinba-server && php composer.phar install && \
    service clickhouse-server restart && \
    clickhouse-client -n < /var/www/pinba-server/clickhouse/pinba.requests.sql && \
    clickhouse-client -n < /var/www/pinba-server/clickhouse/pinba.report_by_all.sql

# protobuf & libevent
RUN apt -y install php-dev libevent-dev && pecl install protobuf && echo "extension=protobuf.so" > /etc/php/7.2/cli/conf.d/protobuf.ini && \
    printf "\n\n /usr/lib/x86_64-linux-gnu/\n\n\nno\n\n\n" | pecl install event && echo "extension=event.so" > /etc/php/7.2/cli/conf.d/event.ini

CMD ["/bin/bash"]

#service clickhouse-server restart
#service grafana-server restart
#service php7.2-fpm restart
