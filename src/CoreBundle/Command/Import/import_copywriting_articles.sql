INSERT INTO ereferer.copywriting_article (
  order_id,
  text,
  meta_title,
  meta_desc,
  corrector_earn,
  writer_earn,
  old_project_id
)
  SELECT
    (SELECT id FROM copywriting_order WHERE copywriting_order.external_id = redaction_reports.project_id),
    text,
    IF(meta_title != "", meta_title, null),
    IF(meta_desc != "", meta_desc, null),
    corrector_earn,
    writer_earn,
    project_id

  FROM ereferer_prod_2.redaction_reports;

DELETE FROM copywriting_article WHERE order_id IS NULL;
