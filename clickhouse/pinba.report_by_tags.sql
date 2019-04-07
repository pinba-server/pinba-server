CREATE TABLE IF NOT EXISTS pinba.report_by_tags
(
    hostname                  String,
    server_name               String,
    script_name               String,
    status                    Int16,
    schema                    String,

    os                        String,
    country                   String,
    http_host                 String,

    req_count                 Int64,

    timestamp                DateTime
) Engine SummingMergeTree
    PARTITION BY toYYYYMM(timestamp)
    PRIMARY KEY (
    timestamp
)
ORDER BY (
    timestamp,
    hostname,
    server_name,
    script_name,
    status,
    schema,
    os,
    country,
    http_host
);


CREATE MATERIALIZED VIEW pinba.v_report_by_tags TO pinba.report_by_tags AS
SELECT
    hostname,
    server_name,
    script_name,
    status,
    schema,

    tags.value[indexOf(tags.name, 'os')] as os,
    tags.value[indexOf(tags.name, 'country')] as country,
    tags.value[indexOf(tags.name, 'http_host')] as http_host,

    sum(req_count) as req_count,

    toStartOfDay(timestamp) as timestamp

FROM pinba.requests
WHERE http_host != ''
GROUP BY hostname, server_name, script_name, status, schema, timestamp, os, country, http_host;

-- DROP TABLE report_by_tags; DROP TABLE v_report_by_tags;