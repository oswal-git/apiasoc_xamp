
DELIMITER ;;

DROP PROCEDURE IF EXISTS `list_notifications_user`;;

CREATE DEFINER=`root`@`localhost` PROCEDURE `list_notifications_user`(IN `Q_id_user` INT, IN `Q_date_last_notification_user` DATETIME) 
BEGIN
        DECLARE W_id_asociation_article int ; 
     	DECLARE W_date_last_notification_user datetime ;
     	DECLARE W_long_name_asociation VARCHAR(100) ;
     	DECLARE W_short_name_asociation CHAR(20) ;
            
        SELECT
            u.id_asociation_user, 
            u.date_last_notification_user, 
            a.long_name_asociation,
            a.short_name_asociation
			INTO W_id_asociation_article, 
                 W_date_last_notification_user,
                 W_long_name_asociation,
                 W_short_name_asociation
        FROM
            users u
        LEFT JOIN asociations a
        ON ( u.id_asociation_user = a.id_asociation)
        WHERE id_user = Q_id_user;
        
        SELECT
            a.id_article,
            a.id_asociation_article,
            a.title_article,
            a.date_notification_article,
            a.publication_date_article,
            IF(
                a.expiration_date_article = '',
                '3000-12-31',
                a.expiration_date_article
            ) AS expiration_date_article,
            W_long_name_asociation as long_name_asociation,
            W_short_name_asociation as short_name_asociation
        FROM
            articles a
        WHERE
            a.id_asociation_article IN (999999999, W_id_asociation_article)
            AND W_date_last_notification_user <= a.date_notification_article 
            AND STR_TO_DATE(
                '2023-06-09
                10:01:57',
                '%Y-%m-%d %h:%i:%s'
            ) <= a.date_notification_article 
            AND a.state_article = 'publicado' 
            AND a.publication_date_article <= FROM_UNIXTIME(
                UNIX_TIMESTAMP(CURRENT_TIMESTAMP),
                '%Y-%m-%d'
            ) 
            AND IF(
                a.expiration_date_article = '',
                '3000-12-31',
                a.expiration_date_article
            ) >= FROM_UNIXTIME(
                UNIX_TIMESTAMP(CURRENT_TIMESTAMP),
                '%Y-%m-%d'
            ) 
            AND a.ind_notify_article = 9;
    END;;


