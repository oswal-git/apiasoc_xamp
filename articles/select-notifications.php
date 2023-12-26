SELECT a.id_article
,a.id_asociation_article
,a.title_article
,a.date_notification_article
,a.publication_date_article
,IF(a.expiration_date_article = '','3000-12-31', a.expiration_date_article) as expiration_date_article
FROM users u
LEFT JOIN articles a
ON u.id_asociation_user = a.id_asociation_article
WHERE u.id_user = 112
AND u.date_last_notification_user <= a.date_notification_article AND a.state_article='publicado' AND
	a.publication_date_article <=from_unixtime(unix_timestamp(CURRENT_TIMESTAMP), '%Y-%m-%d' ) AND
	IF(a.expiration_date_article='' ,'3000-12-31', a.expiration_date_article)>=
	from_unixtime(unix_timestamp(CURRENT_TIMESTAMP), '%Y-%m-%d')
	AND a.ind_notify_article = 9

	UNION

	SELECT a.id_article
	,a.id_asociation_article
	,a.title_article
	,a.date_notification_article
	,a.publication_date_article
	,IF(a.expiration_date_article = '','3000-12-31', a.expiration_date_article) as expiration_date_article
	FROM articles a
	WHERE a.id_asociation_article = 999999999
	-- AND u.date_last_notification_user <= a.date_notification_article AND STR_TO_DATE('2023-06-09
		10:01:57', '%Y-%m-%d %h:%i:%s' ) <=a.date_notification_article AND a.state_article='publicado' AND
		a.publication_date_article <=from_unixtime(unix_timestamp(CURRENT_TIMESTAMP), '%Y-%m-%d' ) AND
		IF(a.expiration_date_article='' ,'3000-12-31', a.expiration_date_article)>=
		from_unixtime(unix_timestamp(CURRENT_TIMESTAMP), '%Y-%m-%d')
		AND a.ind_notify_article = 9