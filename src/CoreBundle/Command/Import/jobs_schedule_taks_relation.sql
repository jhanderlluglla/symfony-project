UPDATE job INNER JOIN schedule_task ON schedule_task.migration_job_id = job.id
SET schedule_task_id = schedule_task.id;
