##### Requirements
- php (with composer)
- clickhouse

##### Installation

- `composer create-project pinba-server/pinba-server`
- `clickhouse-client -n < clickhouse/pinba.requests.sql`
- `clickhouse-client -n < clickhouse/pinba.report_by_all.sql`

##### Usage

- `php pinba-server/workerman_clickhouse.php start -d`
- `php pinba-server/workerman_clickhouse.php stop`

##### Grafana
dashboard [#10011](https://grafana.com/dashboards/10011)
![grafana_dashboard.png](https://raw.githubusercontent.com/pinba-server/pinba-server/master/grafana_dashboard.png)

##### License
MIT License.
