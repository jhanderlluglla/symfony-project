ALTER TABLE copywriting_keyword MODIFY order_id INT;
ALTER TABLE copywriting_keyword MODIFY word VARCHAR(255); #need to allow null value

INSERT INTO ereferer.copywriting_keyword (
  order_id,
  word
)
  SELECT
    (SELECT id FROM copywriting_order WHERE copywriting_order.external_id = redaction_seo_words.project_id),
    IF(expression NOT REGEXP ".*(,|;|\\|).*" and char_length(expression) < 255, convert(cast(convert(TRIM(BOTH ',' FROM expression) using latin1) as binary) using utf8), null)
  FROM ereferer_prod_2.redaction_seo_words;

DELETE FROM copywriting_keyword WHERE order_id is null or word is null;
