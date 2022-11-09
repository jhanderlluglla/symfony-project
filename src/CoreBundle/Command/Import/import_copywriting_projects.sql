INSERT INTO ereferer.copywriting_project (
  customer_id,
  title,
  description,
  is_template,
  created_at,
  language,
  external_id
)
  SELECT
    (SELECT id FROM fos_user WHERE external_id = webmaster_id),
    convert(cast(convert(title using latin1) as binary) using utf8),
    convert(cast(convert(redaction_projects.desc using latin1) as binary) using utf8),
    is_template,
    created_time,
    "fr",
    id

  FROM ereferer_prod_2.redaction_projects;

CREATE INDEX external_id_index ON copywriting_project (external_id) USING BTREE;
