SELECT
    (SELECT username FROM user_usernames WHERE user_id = ac.user_id AND is_default = 1 LIMIT 1) AS username,
    (SELECT email FROM user_emails WHERE user_id = ac.user_id AND is_default = 1 LIMIT 1) AS email,
    (SELECT CONCAT(country_code, subscriber_number, phone_number) FROM user_phones WHERE user_id = ac.user_id AND is_default = 1 LIMIT 1) AS phone_number,
    CONCAT_WS(' ', pr.first_name, pr.middle_name, pr.last_name) AS full_name,
    ac.password,
    ac.user_id
FROM user_accounts ac
LEFT JOIN user_profiles pr ON pr.user_id = ac.user_id
WHERE
    ac.user_id = :query
    OR (SELECT username FROM user_usernames WHERE user_id = ac.user_id AND username = :query LIMIT 1) IS NOT NULL
    OR (SELECT email FROM user_emails WHERE user_id = ac.user_id AND email = :query LIMIT 1) IS NOT NULL
    OR (SELECT 1 FROM user_phones WHERE user_id = ac.user_id AND (CONCAT(country_code, subscriber_number, phone_number) = :query OR CONCAT(subscriber_number, phone_number) = :query) LIMIT 1) IS NOT NULL
    OR md5(ac.user_id) = :query
    OR (SELECT 1 FROM user_usernames WHERE user_id = ac.user_id AND md5(username) = :query LIMIT 1) IS NOT NULL
    OR (SELECT 1 FROM user_emails WHERE user_id = ac.user_id AND md5(email) = :query LIMIT 1) IS NOT NULL