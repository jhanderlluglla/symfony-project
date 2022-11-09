INSERT INTO ereferer.comission (
  netlinking_project_id,
  directory_id,
  user_id,
  amount,
  created_at
)
  SELECT
    (SELECT id
     FROM ereferer.netlinking_project
     WHERE external_id = site_id),
    (SELECT id
     FROM ereferer.directory
     WHERE external_id = annuaire_id),
    (SELECT id
     FROM ereferer.fos_user
     WHERE external_id = webmaster),
    amount,
    FROM_UNIXTIME(date)

  FROM ereferer_prod_2.comissions;
