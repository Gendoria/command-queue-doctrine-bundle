CREATE TABLE IF NOT EXISTS `cmq` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `command_class` VARCHAR(255) NOT NULL,
  `command` LONGTEXT NOT NULL,
  `pool` VARCHAR(100) NOT NULL,
  `failed_no` SMALLINT NOT NULL DEFAULT 0,
  `processed` TINYINT NOT NULL DEFAULT 0,
  `process_after` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE INDEX `cmd_index`
    ON `cmq` (`pool`, `processed`, `process_after`);
    