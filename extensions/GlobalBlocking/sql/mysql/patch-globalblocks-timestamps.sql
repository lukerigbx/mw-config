-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: extensions/GlobalBlocking/sql/abstractschemachanges/patch-globalblocks-timestamps.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
ALTER TABLE  /*_*/globalblocks
CHANGE  gb_timestamp gb_timestamp BINARY(14) NOT NULL,
CHANGE  gb_expiry gb_expiry VARBINARY(14) NOT NULL;