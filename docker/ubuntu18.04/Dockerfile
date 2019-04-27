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

# pinba-server
RUN cd /opt/ && git clone https://github.com/pinba-server/pinba-server.git && cd pinba-server && php composer.phar install && \
    service clickhouse-server restart && \
    clickhouse-client -n < clickhouse/pinba.requests.sql && \
    clickhouse-client -n < clickhouse/pinba.report_by_all.sql && \
    cp systemd/pinba-server.service /usr/lib/systemd/system/pinba-server.service

# protobuf & libevent
RUN apt -y install php-dev libevent-dev && pecl install protobuf && echo "extension=protobuf.so" > /etc/php/7.2/cli/conf.d/protobuf.ini && \
    printf "\n\n /usr/lib/x86_64-linux-gnu/\n\n\nno\n\n\n" | pecl install event && echo "extension=event.so" > /etc/php/7.2/cli/conf.d/event.ini

# nginx
RUN apt install -y nginx

RUN nginx -V

RUN apt install -y zlib1g-dev libgd-dev libxslt1-dev libgeoip-dev && \
    git clone https://github.com/tony2001/ngx_http_pinba_module && \
    wget http://nginx.org/download/nginx-1.14.0.tar.gz && tar zxvf nginx-1.14.0.tar.gz
RUN cd nginx-1.14.0 && ./configure --add-dynamic-module=/ngx_http_pinba_module --with-debug --with-pcre-jit --with-http_ssl_module --with-http_stub_status_module --with-http_realip_module --with-http_auth_request_module --with-http_v2_module --with-http_dav_module --with-http_slice_module --with-threads --with-http_addition_module --with-http_geoip_module=dynamic --with-http_gunzip_module --with-http_gzip_static_module --with-http_image_filter_module=dynamic --with-http_sub_module --with-http_xslt_module=dynamic --with-stream=dynamic --with-stream_ssl_module --with-mail=dynamic --with-mail_ssl_module

# fail!!! https://github.com/tony2001/ngx_http_pinba_module/issues/15
RUN cd nginx-1.14.0 && make

RUN cp /nginx-1.14.0/objs/ngx_http_pinba_module.so /usr/lib64/nginx/modules/ngx_http_pinba_module.so && \
    echo 'load_module /usr/lib64/nginx/modules/ngx_http_pinba_module.so;' > /usr/share/nginx/modules/ngx_http_pinba_module.conf

RUN nginx -t

CMD ["/bin/bash"]

#systemctl daemon-reload
#systemctl enable pinba-server clickhouse-server grafana-server pinba-server php7.2-fpm
#systemctl restart clickhouse-server grafana-server pinba-server php7.2-fpm