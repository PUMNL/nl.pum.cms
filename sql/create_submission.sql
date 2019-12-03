CREATE TABLE IF NOT EXISTS `pum_cms_submission` (
  id integer AUTO_INCREMENT PRIMARY KEY,
  entity varchar(40) not null,
  submission_id integer not null
);
