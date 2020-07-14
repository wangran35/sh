DROP TABLE IF EXISTS `{{file}}`;
DROP TABLE IF EXISTS `{{file_detail}}`;
DROP TABLE IF EXISTS `{{file_share}}`;
DROP TABLE IF EXISTS `{{file_reader}}`;
DROP TABLE IF EXISTS `{{file_dir_access}}`;
DROP TABLE IF EXISTS `{{file_trash}}`;
DROP TABLE IF EXISTS `{{file_cloud_set}}`;
DROP TABLE IF EXISTS `{{file_capacity}}`;

DELETE FROM `{{nav}}` WHERE `module` = 'file';
DELETE FROM `{{node}}` WHERE `module` = 'file';
DELETE FROM `{{node_related}}` WHERE `module` = 'file';
DELETE FROM `{{auth_item}}` WHERE `name` LIKE 'file%';
DELETE FROM `{{auth_item_child}}` WHERE `child` LIKE 'file%';
DELETE FROM `{{menu_common}}` WHERE `module` = 'file';
DELETE FROM `{{setting}}` WHERE `skey` = 'filedefsize';
DELETE FROM `{{setting}}` WHERE `skey` = 'filecompmanager';
DELETE FROM `{{setting}}` WHERE `skey` = 'filecloudopen';
DELETE FROM `{{setting}}` WHERE `skey` = 'filecloudid';
DELETE FROM `{{menu}}` WHERE `m` = 'file';
DELETE FROM `{{cron}}` WHERE `module` = 'recruit';