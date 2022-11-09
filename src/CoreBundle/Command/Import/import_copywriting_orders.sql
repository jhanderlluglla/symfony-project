INSERT INTO ereferer.copywriting_order (
  project_id,
  customer_id,
  copywriter_id,
  exchange_proposition_id,
  title,
  instructions,
  links,
  words_number,
  images_number,
  meta_title,
  meta_description,
  header_one_set,
  header_two_start,
  header_two_end,
  header_three_start,
  header_three_end,
  bold_text,
  ul_tag,
  keywords_per_article_from,
  keywords_per_article_to,
  keyword_in_meta_title,
  keyword_in_header_one,
  keyword_in_header_two,
  keyword_in_header_three,
  amount,
  images_per_article_from,
  images_per_article_to,
  status,
  viewed,
  optimized,
  created_at,
  taken_at,
  ready_for_review_at,
  approved_at,
  external_id
)
  SELECT
    (SELECT id FROM copywriting_project WHERE redaction_projects.id = external_id),
    (SELECT id FROM fos_user WHERE external_id = webmaster_id),
    (SELECT id FROM fos_user WHERE external_id = affectedTO),
    (SELECT id FROM exchange_proposition WHERE external_id = prop_id),
    IF(article_title = "", "Le titre de l'article", convert(cast(convert(article_title using latin1) as binary) using utf8)),
    convert(cast(convert(instructions using latin1) as binary) using utf8),
    IF(links_array != "", convert(cast(convert(links_array using latin1) as binary) using utf8), null),
    words_count,
    img_count,
    meta_title,
    IF(meta_desc != "", meta_desc, null),
    H1_set,
    H2_start,
    H2_end,
    H3_start,
    H3_end,
    bold_text,
    UL_set,
    seo_percent_start,
    seo_percent_end,
    seo_meta_title,
    seo_H1_set,
    seo_H2_set,
    seo_H3_set,
    amount,
    images_count_from,
    images_count_to,
    (CASE
     WHEN status = "finished" THEN "completed"
     WHEN status = "waiting" THEN "waiting"
     WHEN status = "progress" THEN "progress"
     WHEN status = "review" THEN "submitted_to_admin"
     WHEN status = "modification" THEN "declined"
     ELSE "unknown"
     END),
    viewed,
    optimized,
    created_time,
    IF(affected_time != "0000-00-00 00:00:00", affected_time, null),
    IF(affected_time != "0000-00-00 00:00:00", affected_time, null),
    IF(affected_time != "0000-00-00 00:00:00", affected_time, null),
    id

  FROM ereferer_prod_2.redaction_projects;

DELETE copywriting_order FROM copywriting_order INNER JOIN copywriting_project ON copywriting_order.project_id = copywriting_project.id
WHERE copywriting_project.is_template = 1 AND copywriting_project.external_id IS NOT NULL;

CREATE INDEX external_id_index ON copywriting_order (external_id) USING BTREE;

UPDATE copywriting_order set title = "Le titre de l'article" WHERE title IS NULL;
