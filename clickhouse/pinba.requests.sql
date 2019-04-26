CREATE DATABASE IF NOT EXISTS pinba;

CREATE TABLE IF NOT EXISTS pinba.requests
(
  hostname          String,
  server_name       String,
  script_name       String,
  req_time          Float32,
  doc_size          Float32,
  mem_peak_usage    UInt32,
  status            Int16,
  memory_footprint  UInt32,
  schema            String,
  ru_utime          Float32,
  ru_stime          Float32,
  tags              Nested(
    name            String,
    value           String
  ),
  timers            Nested(
    hit_count       UInt32,
    value           Float32,
    tag_name        Array(String),
    tag_value       Array(String)
  ),
  req_count         Int16,
  timestamp         DateTime
)
engine = Null;
-- engine = MergeTree
-- PARTITION BY toYYYYMM(timestamp)
-- ORDER BY (toStartOfMinute(timestamp))
-- SETTINGS index_granularity = 1024;
