SELECT
    ust_created_at as Date,
    CONCAT('@', ust_full_name) as 'Nickname', ust_text as Status,
    CONCAT('https://twitter.com/', ust_full_name, '/status/', ust_status_id) as Link
FROM weaving_dev.weaving_twitter_user_stream ust, weaving_dev.weaving_status_aggregate a
WHERE aggregate_id IN (
    SELECT id FROM weaving_dev.weaving_aggregate
    WHERE name like 'ccc')
AND ust.ust_id = a.status_id
ORDER BY ust_id
DESC LIMIT 100;
