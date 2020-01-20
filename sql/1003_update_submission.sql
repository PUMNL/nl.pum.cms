/*
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 17-Jan-2020
 * @license  AGPL-3.0
 */
alter table `pum_cms_submission`
    add `state` varchar(1) default 'N' not null;
alter table `pum_cms_submission`
    add `submission` text null;
alter table `pum_cms_submission`
    add `failure` text null;