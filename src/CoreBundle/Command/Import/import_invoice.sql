ALTER TABLE invoice MODIFY payer INT;
ALTER TABLE invoice MODIFY file VARCHAR(255);

INSERT INTO ereferer.invoice (
  payer,
  amount,
  vat,
  file,
  number,
  created_at,
  external_id
)
  SELECT
    (SELECT id FROM fos_user WHERE external_id = factures.user),
    amount,
    tva,
    IF(file = "", null, SUBSTR(file, 18)),
    SUBSTR(reference_ereferer, 4),
    FROM_UNIXTIME(time),
    id

  FROM ereferer_prod_2.factures;

DELETE FROM invoice WHERE payer IS NULL;
