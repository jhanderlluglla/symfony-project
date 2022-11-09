INSERT INTO ereferer.directories_list (
  user_id,
  name,
  words_count,
  filter,
  enabled,
  created_at,
  last_seen,
  external_id
)
  SELECT
    (SELECT id
     FROM fos_user
     WHERE external_id = proprietaire),
    SUBSTRING(libelle, 1, 254),
    words_count,
    filter_config,
    (is_deleted = 0),
    FROM_UNIXTIME(created),
    IF(last_viewed != 0, FROM_UNIXTIME(last_viewed), null),
    id
  FROM ereferer_prod_2.annuaireslist;

DELETE FROM directories_list WHERE user_id IS NULL;
