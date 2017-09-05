-- -----------------------------------------------------
-- Table `#__abtag_entry`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `#__abtag_entry`;
CREATE TABLE `#__abtag_entry` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL ,
  `main_tag_id` INT UNSIGNED NOT NULL ,
  `title` VARCHAR(225) NOT NULL ,
  `checked_out` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `asset_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `created_by` INT UNSIGNED NOT NULL ,
  `modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `modified_by` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `idx_created_by` (`created_by` ASC) ,
  INDEX `idx_check_out` (`checked_out` ASC) )
ENGINE = MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;


-- -----------------------------------------------------
-- Table `#__abtag_entry_tag_map`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `#__abtag_entry_tag_map`;
CREATE TABLE `#__abtag_entry_tag_map` (
  `entry_id` INT UNSIGNED NOT NULL ,
  `tag_id` INT UNSIGNED NOT NULL ,
  `ordering` INT NULL DEFAULT NULL ,
  INDEX `idx_entry_id` (`entry_id` ASC) ,
  INDEX `idx_tag_id` (`tag_id` ASC) )
ENGINE = MyISAM DEFAULT CHARSET=utf8;

