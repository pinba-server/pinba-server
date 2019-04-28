FROM centos:7

RUN yum install -y wget curl git

# php
RUN yum install -y epel-release yum-utils
RUN yum install -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
RUN yum-config-manager --enable remi-php73
RUN yum install -y php-cli php-fpm

# pinba extension for php
RUN yum install -y php-pinba

# clickhouse
RUN curl -s https://packagecloud.io/install/repositories/altinity/clickhouse/script.rpm.sh | bash && \
    yum install -y clickhouse-client clickhouse-server

# grafana and clickhouse plugin
RUN yum install -y https://dl.grafana.com/oss/release/grafana-6.1.4-1.x86_64.rpm && \
    grafana-cli plugins install vertamedia-clickhouse-datasource

# pinba-server
RUN cd /opt/ && git clone https://github.com/pinba-server/pinba-server.git && cd pinba-server && php composer.phar install && \
    service clickhouse-server restart && \
    clickhouse-client -n < clickhouse/pinba.requests.sql && \
    clickhouse-client -n < clickhouse/pinba.report_by_all.sql && \
    cp systemd/pinba-server.service /usr/lib/systemd/system/pinba-server.service && \
    sed -i 's|User=www-data|User=nginx|g' /usr/lib/systemd/system/pinba-server.service

# protobuf & libevent
RUN yum -y install php-pecl-protobuf php-pecl-event

# nginx
RUN yum install -y nginx

# pinba module dor nginx
RUN nginx -V

RUN yum -y install gcc pcre-devel openssl-devel libxslt-devel gd-devel perl-ExtUtils-Embed GeoIP-devel gperftools-devel && \
    git clone https://github.com/tony2001/ngx_http_pinba_module && \
    wget http://nginx.org/download/nginx-1.12.2.tar.gz && tar zxvf nginx-1.12.2.tar.gz && cd nginx-1.12.2 && \
    ./configure --add-dynamic-module=/ngx_http_pinba_module --with-file-aio --with-ipv6 --with-http_auth_request_module --with-http_ssl_module --with-http_v2_module --with-http_realip_module --with-http_addition_module --with-http_xslt_module=dynamic --with-http_image_filter_module=dynamic --with-http_geoip_module=dynamic --with-http_sub_module --with-http_dav_module --with-http_flv_module --with-http_mp4_module --with-http_gunzip_module --with-http_gzip_static_module --with-http_random_index_module --with-http_secure_link_module --with-http_degradation_module --with-http_slice_module --with-http_stub_status_module --with-http_perl_module=dynamic --with-mail=dynamic --with-mail_ssl_module --with-pcre --with-pcre-jit --with-stream=dynamic --with-stream_ssl_module --with-google_perftools_module --with-debug && \
    make

RUN cp /nginx-1.12.2/objs/ngx_http_pinba_module.so /usr/lib64/nginx/modules/ngx_http_pinba_module.so && \
    echo 'load_module /usr/lib64/nginx/modules/ngx_http_pinba_module.so;' > /usr/share/nginx/modules/ngx_http_pinba_module.conf

RUN nginx -t

#RUN cat /etc/php-fpm.d/www.conf | grep 9000
#RUN sed -i 's|;pinba|pinba|g' /etc/php.d/40-pinba.ini
#RUN cat /etc/php.d/40-pinba.ini
#RUN sed -i 's|9000|9001|g' /etc/clickhouse-server/config.xml
#RUN cat /etc/clickhouse-server/config.xml | grep 900

CMD ["/bin/bash"]

#systemctl daemon-reload
#systemctl enable pinba-server clickhouse-server grafana-server pinba-server php7.3-fpm
#systemctl restart clickhouse-server grafana-server pinba-server php7.3-fpm