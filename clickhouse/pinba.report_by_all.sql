CREATE TABLE IF NOT EXISTS pinba.report_by_all
(
    hostname                  String,
    server_name               String,
    script_name               String,
    status                    Int16,
    schema                    String,
    req_count                 Int64,

    req_time_sum              Float64,
    ru_utime_sum              Float64,
    ru_stime_sum              Float64,
    doc_size_sum              Float64,

    req_time_max              Float32,
    ru_utime_max              Float32,
    ru_stime_max              Float32,
    doc_size_max              Float32,

    mem_peak_usage_max        UInt32,
    memory_footprint_max      UInt32,

    timestamp                 DateTime
) Engine MergeTree
PARTITION BY toYYYYMM(timestamp)
ORDER BY (timestamp, hostname, server_name, script_name, status, schema);

CREATE MATERIALIZED VIEW pinba.v_report_by_all TO pinba.report_by_all AS
SELECT
    hostname,
    server_name,
    script_name,
    status,
    schema,

    sum(req_count) as req_count,
    sum(req_time) as req_time_sum,
    sum(ru_utime) as ru_utime_sum,
    sum(ru_stime) as ru_stime_sum,
    sum(doc_size) as doc_size_sum,

    max(req_time) as req_time_max,
    max(ru_utime) as ru_utime_max,
    max(ru_stime) as ru_stime_max,
    max(doc_size) as doc_size_max,

    max(mem_peak_usage)   as mem_peak_usage_max,
    max(memory_footprint) as memory_footprint_max,

    toStartOfMinute(timestamp) as timestamp

FROM pinba.requests
GROUP BY hostname, server_name, script_name, status, schema, timestamp;

-- DROP TABLE report_by_all; DROP TABLE v_report_by_all;

---

CREATE TABLE IF NOT EXISTS pinba.nginx_report_by_all
(
    hostname             String,
    server_name          String,
    script_name          String,
    status               Int16,
    schema               String,
    req_count            Int64,

    req_time_sum         Float64,
    ru_utime_sum         Float64,
    ru_stime_sum         Float64,
    doc_size_sum         Float64,

    req_time_max         Float32,
    ru_utime_max         Float32,
    ru_stime_max         Float32,
    doc_size_max         Float32,

    mem_peak_usage_max   UInt32,
    memory_footprint_max UInt32,

    timestamp            DateTime
) Engine MergeTree
PARTITION BY toYYYYMM(timestamp)
ORDER BY (timestamp, hostname, server_name, script_name, status, schema);

CREATE MATERIALIZED VIEW pinba.v_nginx_report_by_all TO pinba.nginx_report_by_all AS
SELECT
    hostname,
    server_name,
    if(script_name LIKE '/%/%.%', replaceRegexpOne(script_name, '/([^/]*)/.*\.([^.]+)$', '/\\1/*'), script_name) as script_name,
    status,
    schema,

    sum(req_count) as req_count,
    sum(req_time) as req_time_sum,
    sum(ru_utime) as ru_utime_sum,
    sum(ru_stime) as ru_stime_sum,
    sum(doc_size) as doc_size_sum,

    max(req_time) as req_time_max,
    max(ru_utime) as ru_utime_max,
    max(ru_stime) as ru_stime_max,
    max(doc_size) as doc_size_max,

    max(mem_peak_usage)   as mem_peak_usage_max,
    max(memory_footprint) as memory_footprint_max,

    toStartOfMinute(timestamp) as timestamp

FROM pinba.nginx_requests
GROUP BY hostname, server_name, script_name, status, schema, timestamp;
