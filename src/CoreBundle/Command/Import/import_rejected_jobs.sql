INSERT INTO ereferer.job (
  netlinking_project_id,
  affected_user_id,
  rating,
  comment,
  rating_added_at,
  take_at,
  status,
  cost_writer,
  cost_webmaster,
  created_at,
  completed_at,
  rejected_at,
  migration_annuaire_directory
)
  SELECT
    (SELECT id
     FROM ereferer.netlinking_project
     WHERE external_id = project_id),
    (SELECT affected_user_id
     FROM ereferer.netlinking_project
     WHERE external_id = project_id),
    (SELECT value
     FROM ereferer_prod_2.writers_likes
     WHERE
       writers_likes.project_id = jobs_rejected.project_id AND writers_likes.annuaire_id = jobs_rejected.annuaire_id),
    (SELECT webmaster_comment
     FROM ereferer_prod_2.writers_likes
     WHERE
       writers_likes.project_id = jobs_rejected.project_id AND writers_likes.annuaire_id = jobs_rejected.annuaire_id),
    (SELECT FROM_UNIXTIME(clicked_time)
     FROM ereferer_prod_2.writers_likes
     WHERE
       writers_likes.project_id = jobs_rejected.project_id AND writers_likes.annuaire_id = jobs_rejected.annuaire_id),
    (SELECT affected_at
     FROM ereferer.netlinking_project
     WHERE external_id = project_id),
    "rejected",
    0.0,
    0.0,
    IF((SELECT id
        FROM ereferer.netlinking_project
        WHERE external_id = project_id), (SELECT IF(affected_at, affected_at, created_at)
                                          FROM ereferer.netlinking_project
                                          WHERE external_id = project_id), FROM_UNIXTIME(0)),
    IF((SELECT id
        FROM ereferer.netlinking_project
        WHERE external_id = project_id), (SELECT IF(affected_at, affected_at, created_at)
                                          FROM ereferer.netlinking_project
                                          WHERE external_id = project_id), FROM_UNIXTIME(0)),
    IF((SELECT id
        FROM ereferer.netlinking_project
        WHERE external_id = project_id), (SELECT IF(affected_at, affected_at, created_at)
                                          FROM ereferer.netlinking_project
                                          WHERE external_id = project_id), FROM_UNIXTIME(0)),
    annuaire_id

  FROM ereferer_prod_2.jobs_rejected;

DELETE FROM ereferer.job
WHERE netlinking_project_id IS NULL;

UPDATE job
SET rating_added_at = completed_at
WHERE rating IS NOT NULL AND YEAR(rating_added_at) < 2000;

