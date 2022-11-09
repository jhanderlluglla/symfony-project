INSERT IGNORE INTO ereferer.netlinking_project_comments (
  user_id,
  job_id,
  comment,
  created_at,
  external_id
)
  SELECT
    (SELECT id
     FROM ereferer.fos_user
     WHERE external_id = commentaires.user),
    (SELECT id
     FROM ereferer.job
     WHERE migration_netlinking_project = idprojet AND
           migration_annuaire_directory = idannuaire),
    com,
    FROM_UNIXTIME(created),
    id

  FROM ereferer_prod_2.commentaires;

INSERT INTO netlinking_project_comments (user_id, comment, created_at, job_id)
  SELECT affected_user_id, "This comment was generated", job.created_at, job.id FROM job
  LEFT JOIN netlinking_project_comments ON netlinking_project_comments.job_id = job.id
WHERE netlinking_project_comments.id is null;

DELETE FROM netlinking_project_comments WHERE user_id IS NULL or job_id IS NULL;

#
# SELECT * FROM job
#   LEFT JOIN netlinking_project_comments ON netlinking_project_comments.job_id = job.id
#   LEFT JOIN directory_backlinks ON directory_backlinks.job_id = job.id
# WHERE netlinking_project_comments.id is null and directory_backlinks.id is not null;
