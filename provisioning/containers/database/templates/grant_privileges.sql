CREATE USER test WITH password '{ password }' CREATEDB;
CREATE DATABASE snapshots_test;
ALTER DATABASE snapshots_test OWNER TO test;
GRANT ALL PRIVILEGES ON DATABASE snapshots_test TO test;