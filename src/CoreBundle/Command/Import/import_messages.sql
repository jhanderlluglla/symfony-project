INSERT INTO ereferer.message (
  send_user_id,
  receive_user_id,
  subject,
  content,
  is_read,
  read_at,
  created_at,
  external_id
)
  SELECT
    (SELECT id FROM fos_user WHERE external_id = sender),
    (SELECT id FROM fos_user WHERE external_id = receiver),
    objet,
    content,
    viewReceiver,
    FROM_UNIXTIME(time),
    FROM_UNIXTIME(time),
    id

  FROM ereferer_prod_2.messages;

DELETE FROM message WHERE send_user_id is NULL or receive_user_id is NULL;
