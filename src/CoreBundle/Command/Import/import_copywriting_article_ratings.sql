INSERT INTO ereferer.copywriting_article_rating (
  order_id,
  value,
  comment,
  created_at
)
  SELECT
    (SELECT id FROM copywriting_order WHERE copywriting_order.external_id = redaction_writers_likes.project_id),
    value,
    convert(cast(convert(webmaster_comment using latin1) as binary) using utf8),
    IF(clicked_time != 0, FROM_UNIXTIME(clicked_time), NOW())
  FROM ereferer_prod_2.redaction_writers_likes;

DELETE FROM copywriting_article_rating WHERE order_id IS NULL;
