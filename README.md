##### Requirements
- php
- clickhouse

##### Installation

- `cd /opt`
- `git clone https://github.com/pinba-server/pinba-server.git`
- `cd pinba-server`
- `php composer.phar install`
- `clickhouse-client -n < clickhouse/pinba.requests.sql`

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
- publications: [reddit(en)](https://www.reddit.com/r/PHP/comments/bigszu/statistics_and_monitoring_of_php_scripts_in_real/), [habr(ru)](https://habr.com/ru/post/444610/)
- the installation of ClickHouse, pinba-server, pinba module for php and nginx on [Ubuntu 18.04 LTS](https://github.com/pinba-server/pinba-server/blob/master/docker/ubuntu18.04/Dockerfile) and [Centos 7](https://github.com/pinba-server/pinba-server/blob/master/docker/centos7/Dockerfile).

##### Grafana
dashboard [#10011](https://grafana.com/dashboards/10011)

![grafana_dashboard.png](https://raw.githubusercontent.com/pinba-server/pinba-server/master/grafana_dashboard.png)

##### License
MIT License.

##### See also
- [ClickHouse-Ninja/Proton](https://github.com/ClickHouse-Ninja/Proton) - golang version of pinba-server for clickhouse 
- [olegfedoseev/pinba-influxdb](https://github.com/olegfedoseev/pinba-influxdb) - golang version of pinba-server for influxdb
