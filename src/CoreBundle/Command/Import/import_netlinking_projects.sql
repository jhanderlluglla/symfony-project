INSERT INTO ereferer.netlinking_project (
  url,
  directory_list_id,
  user_id,
  affected_user_id,
  affected_by_user_id,
  frequency_directory,
  frequency_day,
  status,
  created_at,
  started_at,
  finished_at,
  affected_at,
  comment,
  words_count,
  external_id
)
  SELECT
    IF((TRIM(BOTH '\n' FROM lien) LIKE "%\n%") = 0, TRIM(BOTH '\n' FROM lien), "https://remove-me.com"),
    (SELECT id
     FROM ereferer.directories_list
     WHERE external_id = annuaire),
    (SELECT id
     FROM ereferer.fos_user
     WHERE external_id = proprietaire),
    (SELECT id
     FROM ereferer.fos_user
     WHERE external_id = affectedTO),
    (SELECT id
     FROM ereferer.fos_user
     WHERE external_id = affectedBY),
    SUBSTRING_INDEX(frequence, 'x', 1),
    REVERSE(SUBSTRING_INDEX(REVERSE(frequence), 'x', 1)),
    IF(over, "finished", IF(affectedTO, "in_progress", IF(adminApprouve, "waiting", "nostart"))),
    FROM_UNIXTIME(sendTime),
    FROM_UNIXTIME(sendTime),
    IF(overTime = 0, null, FROM_UNIXTIME(overTime)),
    IF(affectedTO and affectedTime, FROM_UNIXTIME(affectedTime), FROM_UNIXTIME(sendTime)),
    consignes,
    0,
    id

  FROM ereferer_prod_2.projets;

DELETE FROM netlinking_project WHERE url = "https://remove-me.com";
DELETE FROM netlinking_project WHERE directory_list_id IS NULL OR user_id IS NULL;
DELETE FROM netlinking_project WHERE affected_user_id IS NULL AND status = "in_progress";

CREATE INDEX external_id_index ON netlinking_project (external_id) USING BTREE;

UPDATE netlinking_project set url = CONCAT("http://", url) WHERE url NOT LIKE "http%";

UPDATE netlinking_project INNER JOIN directories_list on netlinking_project.directory_list_id = directories_list.id
  set netlinking_project.words_count = directories_list.words_count
  WHERE directories_list.words_count is not NULL and directories_list.words_count != 0;

