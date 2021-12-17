--
-- Email Queue 發件列表
--
CREATE TABLE IF NOT EXISTS `email_queue` (
  `id`       bigint(20)  NOT NULL AUTO_INCREMENT,
  `to_email` varchar(32) NOT NULL,
  `subject`  longtext    NOT NULL,
  `template` longtext    NOT NULL,
  `array`    longtext    NOT NULL,
  `time`     int(64)     NOT NULL,
  primary key (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Email Queue 發件列表';

