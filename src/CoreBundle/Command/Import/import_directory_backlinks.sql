INSERT IGNORE INTO ereferer.directory_backlinks (
  job_id,
  backlink,
  date_checked,
  date_checked_first,
  date_found,
  status,
  status_type
)
  SELECT
    (SELECT id
     FROM ereferer.job
     WHERE migration_netlinking_project = site_id AND migration_annuaire_directory = annuaire_id),
    IF(backlink = "", NULL, convert(cast(convert(trim(backlink) using latin1) as binary) using utf8)),
    date_checked,
    date_checked_first,
    IF(date_found = "0000-00-00 00:00:00", NULL, date_found),
    status,
    status_type

  FROM ereferer_prod_2.annuaires2backlinks;

DELETE FROM directory_backlinks WHERE job_id IS NULL;
UPDATE directory_backlinks SET backlink = NULL WHERE backlink = "";
UPDATE directory_backlinks SET backlink = CONCAT('http://', backlink) WHERE backlink NOT LIKE 'http%';

INSERT INTO ereferer.directory_backlinks (
  job_id,
  backlink,
  date_checked,
  date_checked_first,
  date_found,
  status,
  status_type
)
  SELECT
    job.id,
    null,
    job.affected_at,
    job.affected_at,
    null,
    "not_found_yet",
    "cron"
  FROM ereferer.job LEFT JOIN directory_backlinks on job.id = directory_backlinks.job_id
  WHERE directory_backlinks.id is null and job.status = "completed";
