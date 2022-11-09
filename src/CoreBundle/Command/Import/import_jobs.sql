ALTER TABLE ereferer.job
  ADD COLUMN migration_annuaire_directory INT
  AFTER id;

ALTER TABLE ereferer.job
  ADD COLUMN migration_netlinking_project INT
  AFTER id;

INSERT INTO ereferer.job (
  netlinking_project_id,
  affected_user_id,
  take_at,
  status,
  cost_writer,
  cost_webmaster,
  created_at,
  completed_at,
  external_id,
  migration_annuaire_directory,
  migration_netlinking_project
)
  SELECT
    (SELECT id
     FROM ereferer.netlinking_project
     WHERE external_id = siteID),
    (SELECT id
     FROM ereferer.fos_user
     WHERE external_id = affectedto),
    (FROM_UNIXTIME(IF(soumissibleTime < affectedTime AND soumissibleTime < created, soumissibleTime,
                      IF(affectedTime = 0, IF(soumissibleTime < created, soumissibleTime, created), affectedTime)))),
    (CASE
     WHEN adminApprouved = 2 THEN "completed"
     WHEN adminApprouved = 3 THEN "impossible"
     ELSE "unknown"
     END),
    coutReferer,
    coutWebmaster,
    FROM_UNIXTIME(created),
    (FROM_UNIXTIME(IF(soumissibleTime > adminApprouvedTime, soumissibleTime, adminApprouvedTime))),
    id,
    annuaireID,
    siteID

  FROM ereferer_prod_2.jobs;

DELETE FROM job WHERE netlinking_project_id IS NULL;

CREATE INDEX migration_annuaire_directory_index ON job (migration_annuaire_directory) USING BTREE;
CREATE INDEX migration_netlinking_project_index ON job (migration_netlinking_project) USING BTREE;

UPDATE job INNER JOIN ereferer_prod_2.writers_likes
    ON migration_netlinking_project = project_id and migration_annuaire_directory = annuaire_id
set rating = value, comment = webmaster_comment, rating_added_at = FROM_UNIXTIME(clicked_time);

UPDATE job
SET rating_added_at = completed_at
WHERE rating IS NOT NULL AND YEAR(rating_added_at) < 2000;
