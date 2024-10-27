CREATE TABLE `wp_exportsreports_groups`
(
	`id`          INT(10) NOT NULL AUTO_INCREMENT,
	`name`        VARCHAR(255) NOT NULL,
	`disabled`    INT(1) NOT NULL,
	`role_access` mediumtext   NOT NULL,
	`weight`      INT(10) NOT NULL,
	`created`     datetime     NOT NULL,
	`updated`     datetime     NOT NULL,
	PRIMARY KEY ( `id` )
) DEFAULT CHARSET=utf8;

CREATE TABLE `wp_exportsreports_reports`
(
	`id`               INT(10) NOT NULL AUTO_INCREMENT,
	`name`             VARCHAR(255) NOT NULL,
	`group`            INT(10) NOT NULL,
	`disabled`         INT(1) NOT NULL,
	`disable_export`   INT(1) NOT NULL,
	`default_none`     INT(1) NOT NULL,
	`role_access`      mediumtext   NOT NULL,
	`sql_query`        longtext     NOT NULL,
	`sql_query_count`  longtext     NOT NULL,
	`page_orientation` MEDIUMTEXT,
	`field_data`       longtext     NOT NULL,
	`weight`           INT(10) NOT NULL,
	`created`          datetime     NOT NULL,
	`updated`          datetime     NOT NULL,
	PRIMARY KEY ( `id` )
) DEFAULT CHARSET=utf8;

CREATE TABLE `wp_exportsreports_log`
(
	`id`        INT(10) NOT NULL AUTO_INCREMENT,
	`report_id` INT(10) NOT NULL,
	`filename`  VARCHAR(255) NOT NULL,
	`created`   datetime     NOT NULL,
	`updated`   datetime     NOT NULL,
	PRIMARY KEY ( `id` )
) DEFAULT CHARSET=utf8;

INSERT INTO `wp_exportsreports_groups`
VALUES ( '1', 'WordPress', '0', '', '0', NOW( ), NOW( ) );

INSERT INTO `wp_exportsreports_reports`
VALUES (
		   '1', 'Example Posts Report', '1', '0', '0', '0', '',
		   'SELECT ID,post_title,post_name,post_date,post_content FROM wp_posts WHERE post_type=\'post\'', '', 'L',
		   '[{\"name\":\"ID\",\"label\":\"Post ID\",\"hide_report\":\"0\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"text\"},{\"name\":\"post_title\",\"label\":\"Post Title\",\"hide_report\":\"0\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"text\"},{\"name\":\"post_name\",\"label\":\"Post\'s Slug\",\"hide_report\":\"0\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"text\"},{\"name\":\"post_date\",\"label\":\"Post Date\",\"hide_report\":\"0\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"date\"},{\"name\":\"post_content\",\"label\":\"Post Content\",\"hide_report\":\"1\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"text\"}]', '0', NOW(), NOW());
INSERT INTO `wp_exportsreports_reports` VALUES ('2', 'Example Pages Report', '1', '0', '0', '1', '', 'SELECT ID,post_title,post_name,post_date,post_content FROM wp_posts WHERE post_type=\'page\'', '', 'L', '[{\"name\":\"ID\",\"label\":\"Page ID\",\"hide_report\":\"0\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"text\"},{\"name\":\"post_title\",\"label\":\"Page Title\",\"hide_report\":\"0\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"text\"},{\"name\":\"post_name\",\"label\":\"Post\'s Slug\",\"hide_report\":\"0\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"text\"},{\"name\":\"post_date\",\"label\":\"Page Date\",\"hide_report\":\"0\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"date\"},{\"name\":\"post_content\",\"label\":\"Page Content\",\"hide_report\":\"1\",\"hide_export\":\"0\",\"custom_display\":\"\",\"type\":\"text\"}]', '1', NOW(), NOW());
