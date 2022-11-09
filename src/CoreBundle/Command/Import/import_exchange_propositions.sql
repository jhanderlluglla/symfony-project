INSERT INTO ereferer.exchange_proposition (
  user_id,
  exchange_site_id,
  redac,
  status,
  page_publish,
  words_number,
  links_number,
  images_number,
  credits,
  created_at,
  updated_at,
  accepted_at,
  published_at,
  plaintext,
  check_links,
  document_link,
  document_image,
  viewed,
  comments,
  modification_comment,
  modification_status,
  modification_close,
  modification_refuse_comment,
  rate_stars,
  rate_comment,
  instructions,
  article_author_type,
  external_id
)
  SELECT
    (SELECT id FROM fos_user WHERE external_id = from_user_id),
    (SELECT id FROM exchange_site WHERE external_id = to_site_id),
    redac,
    (CASE
       WHEN ech_status = 0 THEN "awaiting_webmaster"
       WHEN ech_status = 10 THEN "awaiting_writer"
       WHEN ech_status = 30 THEN "in_progress"
       WHEN ech_status = 50 THEN "changed"
       WHEN ech_status = 100 THEN "refused"
       WHEN ech_status = 110 THEN "expired"
       WHEN ech_status = 111 THEN "impossible"
       WHEN ech_status = 200 THEN "accepted"
       WHEN ech_status = 201 THEN "published"
       ELSE "unknown"
     END),
    IF(page_publish = "0", null, page_publish),
    nb_mots,
    nb_liens,
    nb_images,
    (credit_cost * 10),
    FROM_UNIXTIME(datetime_created),
    FROM_UNIXTIME(datetime_created),
    IF(datetime_accepted = 0, FROM_UNIXTIME(datetime_created), FROM_UNIXTIME(datetime_accepted)),
    IF(datetime_accepted = 0, FROM_UNIXTIME(datetime_created), FROM_UNIXTIME(datetime_accepted)),
    convert(cast(convert(plaintext using latin1) as binary) using utf8),
    IF(check_links = "", "N;", convert(cast(convert(check_links using latin1) as binary) using utf8)),
    doc_link,
    doc_img,
    viewed,
    echanges_proposition.comment,
    convert(cast(convert(mod_client using latin1) as binary) using utf8),
    mod_status,
    mod_close,
    convert(cast(convert(mod_refuse using latin1) as binary) using utf8),
    avis_note,
    convert(cast(convert(avis_com using latin1) as binary) using utf8),
    convert(cast(convert(consigne using latin1) as binary) using utf8),
    "unknown_type", #chaned after update_exchange_proposals file
    id


  FROM ereferer_prod_2.echanges_proposition;

DELETE FROM exchange_proposition WHERE exchange_site_id is NULL;
