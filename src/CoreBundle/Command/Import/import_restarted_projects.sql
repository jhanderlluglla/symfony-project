INSERT INTO ereferer.restarted_project (
  restarted_by_id,
  affected_user_id,
  netlinking_project_id,
  directory_id,
  first_restart,
  latest_restart,
  first_resoumission,
  latest_resoumission,
  original_soumission,
  restart_count
)
  SELECT
    (SELECT id FROM fos_user WHERE external_id = RestartedBy),
    (SELECT id FROM fos_user WHERE external_id = referenceur_id),
    (SELECT id FROM netlinking_project WHERE external_id = project_id),
    (SELECT id FROM directory WHERE external_id = annuaire_id),
    FROM_UNIXTIME(FirstRestart),
    FROM_UNIXTIME(LatestRestart),
    FROM_UNIXTIME(FirstResoumission),
    FROM_UNIXTIME(LatestResoumission),
    FROM_UNIXTIME(originalSoumission),
    RestartCount


  FROM ereferer_prod_2.restartprojects;
