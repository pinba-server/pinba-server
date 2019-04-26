##### Requirements
- php
- clickhouse

##### Installation

- `cd /var/www`
- `git clone https://github.com/pinba-server/pinba-server.git`
- `cd pinba-server`
- `php composer.phar install`
- `clickhouse-client -n < clickhouse/pinba.requests.sql`
- `clickhouse-client -n < clickhouse/pinba.report_by_all.sql`

##### Usage

- `php workerman_clickhouse.php start -d`
- `php workerman_clickhouse.php stop`

##### Systemd autostart script
- `sudo cp systemd/pinba-server.service /usr/lib/systemd/system/pinba-server.service`
- `sudo systemctl daemon-reload && systemctl enable pinba-server && systemctl start pinba-server`

##### Stats for 1kk requests (24 hours with about 10 RPS):

|table|rows|size, Mb|description|
|---|---|---|---|
|requests|1kk|26|raw data|
|report_by_all|56k|2|aggregated data by minutes|

##### Info
- pinba server uses 30002 port on 127.0.0.1
- for better performance you can install php-extensions: `apt install php-pecl-protobuf` and `pecl install event`
- don't forget to install pinba client for php: `apt install php-pinba` and clickhouse

##### Grafana
dashboard [#10011](https://grafana.com/dashboards/10011)

![grafana_dashboard.png](https://raw.githubusercontent.com/pinba-server/pinba-server/master/grafana_dashboard.png)

##### License
MIT License.

##### See also
- [ClickHouse-Ninja/Proton](https://github.com/ClickHouse-Ninja/Proton) - golang version of pinba-server for clickhouse 
- [olegfedoseev/pinba-influxdb](https://github.com/olegfedoseev/pinba-influxdb) - golang version of pinba-server for influxdb
