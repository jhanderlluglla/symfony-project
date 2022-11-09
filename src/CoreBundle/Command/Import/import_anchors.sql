ALTER TABLE anchor MODIFY name TEXT;

INSERT INTO ereferer.anchor (
  netlinking_project_id,
  directory_id,
  name,
  created_at
)
  SELECT
    (SELECT id
     FROM ereferer.netlinking_project
     WHERE external_id = projetID),
    (SELECT id
     FROM ereferer.directory
     WHERE external_id = annuaireID),
    IF(ancre = "", null, ancre),
    FROM_UNIXTIME(created)

  FROM ereferer_prod_2.ancres;

DELETE FROM anchor WHERE name is NULL;
DELETE FROM anchor where exchange_site_id is null and directory_id is null;
