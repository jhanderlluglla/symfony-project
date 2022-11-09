INSERT INTO ereferer.exchange_site (
  user_id,
  url,
  hide_url,
  credits,
  active,
  accept_eref,
  accept_web,
  tags,
  accept_self,
  min_words_number,
  max_links_number,
  min_images_number,
  max_images_number,
  publication_rules,
  created_at,
  external_id
)
  SELECT
    (SELECT id FROM fos_user WHERE external_id = user_id),
    TRIM(BOTH '/' FROM url),
    masquer,
    (credit * 10),
    active,
    accept_eref,
    accept_web,
    tags,
    accept_self,
    nb_mots_max,
    nb_liens_max,
    nb_images_min,
    nb_images_min,
    regle,
    NOW(),
    id

  FROM ereferer_prod_2.echange_sites;

DELETE FROM exchange_site WHERE user_id IS NULL;

UPDATE erefer.exchange_site
SET min_words_number = 100
WHERE min_words_number IS NULL;
