INSERT INTO ereferer.transaction (
  user_id,
  description,
  debit,
  credit,
  solder,
  created_at
)
  SELECT
    id,
    'credit.convert',
    credit * 10,
    0.00,
    balance,
    NOW()

  FROM ereferer.fos_user WHERE fos_user.credit IS NOT NULL AND fos_user.credit != 0;
