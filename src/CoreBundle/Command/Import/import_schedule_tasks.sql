ALTER TABLE ereferer.schedule_task
  ADD COLUMN migration_job_id VARCHAR(80)
  AFTER id;

INSERT INTO ereferer.schedule_task (
  directory_id,
  netlinking_project_id,
  start_at,
  migration_job_id
)
  SELECT
    (SELECT id
     FROM ereferer.directory
     WHERE external_id = migration_annuaire_directory),
    netlinking_project_id,
    take_at,
    id

  FROM ereferer.job WHERE job.external_id IS NOT NULL OR job.status = 'rejected';

CREATE INDEX migration_job_id_index ON schedule_task (migration_job_id) USING BTREE;

INSERT INTO ereferer.schedule_task(directory_id,
                                   netlinking_project_id,
                                   start_at)
  SELECT all_directories.directory_id,
    all_directories.project_id,
    '2222-02-22 22:22:22'
  FROM ereferer.schedule_task
    RIGHT JOIN
    (SELECT np.id AS project_id, directories_list_directory.directory_id
     FROM directories_list_directory
       INNER JOIN directories_list dl ON directories_list_directory.directory_list_id = dl.id
       INNER JOIN netlinking_project np ON dl.id = np.directory_list_id
     WHERE np.status = 'in_progress') AS all_directories
      ON all_directories.directory_id = schedule_task.directory_id AND
         all_directories.project_id = schedule_task.netlinking_project_id
  WHERE schedule_task.id IS NULL;

DELETE FROM ereferer.schedule_task WHERE schedule_task.directory_id IS NULL AND schedule_task.exchange_site_id IS NULL;

# SELECT DATEDIFF(job.affected_at, netlinking_project.affected_at)
# FROM `job` INNER JOIN netlinking_project on job.netlinking_project_id = netlinking_project.id
# WHERE job.external_id is not null
# ORDER BY DATEDIFF(netlinking_project.affected_at, job.affected_at) DESC
