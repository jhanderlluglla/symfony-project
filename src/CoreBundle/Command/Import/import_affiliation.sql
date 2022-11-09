INSERT INTO ereferer.affiliation (
  parent_id,
  affiliation_id,
  tariff,
  created_at
)
  SELECT
    (SELECT id from ereferer.fos_user where external_id = parrain),
    (SELECT id from ereferer.fos_user where external_id = affilie),
    amount,
    FROM_UNIXTIME(time)

  FROM ereferer_prod_2.affiliation_affilies;

DELETE FROM affiliation WHERE parent_id IS NULL OR affiliation_id IS NULL;

INSERT INTO ereferer.affiliation_click (
  affiliation_id,
  created_at
)
  SELECT
    (SELECT id from ereferer.fos_user where external_id = parrain),
    FROM_UNIXTIME(time)

  FROM ereferer_prod_2.affiliation_click;

DELETE FROM affiliation_click WHERE affiliation_id IS NULL;
