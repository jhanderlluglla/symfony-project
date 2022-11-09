INSERT INTO ereferer.withdraw_request (
  user_id,
  withdraw_amount,
  commission_percent,
  status,
  created_at,
  reviewed_at,
  external_id
)
  SELECT
    (SELECT id FROM fos_user WHERE external_id = requesterID),
    requestAmount,
    (0),
    (CASE
     WHEN answer = 1 THEN "accepted"
     WHEN answer = 0 THEN "rejected"
     END),
    created,
    FROM_UNIXTIME(answerTime),
    id

  FROM ereferer_prod_2.requestpayment;

DELETE FROM withdraw_request WHERE user_id IS NULL;
