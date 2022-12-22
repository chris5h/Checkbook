CREATE TABLE `transactions` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`created_dt` DATETIME NOT NULL DEFAULT current_timestamp(),
	`trans_date` DATE NOT NULL,
	`description` VARCHAR(1024) NOT NULL,
	`amount` DECIMAL(9,2) NOT NULL,
	`checknumber` INT(11) NULL DEFAULT NULL,
	`schedule_id` INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE `schedule` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`created_dt` DATETIME NOT NULL DEFAULT current_timestamp(),
	`dayofmonth` INT(11) NOT NULL,
	`amount` DECIMAL(9,2) NOT NULL,
	`description` VARCHAR(1024) NOT NULL,
	`active` BIT(1) NOT NULL DEFAULT b'1',
	PRIMARY KEY (`id`)
);

CREATE VIEW scheduledtransactions AS
	SELECT
		`b`.`nextrun` AS `nextrun`,
		`b`.`description` AS `description`,
		`b`.`amount` AS `amount`,
		`b`.`id` AS `id`
	FROM (
		(
			select 
				`s`.`id` AS `id`,
				`s`.`created_dt` AS `created_dt`,
				`s`.`dayofmonth` AS `dayofmonth`,
				`s`.`amount` AS `amount`,
				`s`.`description` AS `description`,		
				`s`.`active` AS `active`,
				STR_TO_DATE(CONCAT(REPLACE(month(curdate()) + if(dayofmonth(curdate()) > `s`.`dayofmonth`,1,0),13,1),',',`s`.`dayofmonth`,',',year(curdate())),'%m,%d,%Y') AS `nextrun`
			from `schedule` `s` where `s`.`active` = 1
		) `b` left join `transactions` `t` ON 
			(
				`t`.`trans_date` = `b`.`nextrun` and `b`.`id` = `t`.`schedule_id`
			)
		) where `t`.`id` is null and `b`.`nextrun` between curdate() and CURDATE() + interval 6 DAY;

CREATE VIEW allschedules
AS
SELECT a.*, b.lastrun FROM (
	SELECT 
		*, 
		str_to_date(concat(month(curdate()) + if(dayofmonth(curdate()) > s.dayofmonth,1,0),',',s.dayofmonth,',',year(curdate())),'%m,%d,%Y') AS `nextrun`
	FROM `schedule` s
) a LEFT JOIN (
	SELECT schedule_id , MAX(t.trans_date) AS `lastrun` FROM transactions t 
		WHERE t.schedule_id IS NOT NULL
	GROUP BY t.schedule_id
) b ON id = b.schedule_id;

SET GLOBAL event_scheduler = ON;

DELIMITER //

CREATE PROCEDURE InsertScheduledTrans()
BEGIN
	INSERT INTO transactions (`trans_date`, `description`, `amount`, `schedule_id`)
	SELECT
		s.nextrun,
		s.description,	
		s.amount,
		s.id
	FROM scheduledtransactions s;
END //

DELIMITER ;


CREATE EVENT event_name
	ON SCHEDULE
		EVERY 1 DAY
		STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 1 HOUR)
	DO
		CALL InsertScheduledTrans;