INSERT INTO ereferer.category VALUES (NULL, 1, NULL, "FAKE", 0, 0, 0, 'FF', 99999);

INSERT INTO ereferer.exchange_site_category (
  exchange_site_id,
  category_id
)
  SELECT
    (SELECT id
     FROM exchange_site
     WHERE external_id = echange_sites.id),
    (SELECT id
     FROM category
     WHERE external_id = echange_sites.cat_id1)

  FROM ereferer_prod_2.echange_sites;


INSERT INTO ereferer.exchange_site_category (
  exchange_site_id,
  category_id
)
  SELECT
    (SELECT id
     FROM exchange_site
     WHERE external_id = echange_sites.id),
    IF(
        ((SELECT id
         FROM category
         WHERE external_id = echange_sites.cat_id2) IS NULL OR echange_sites.cat_id1 = echange_sites.cat_id2),
        (SELECT id
         FROM category
         WHERE external_id = 99999),
        (SELECT id
         FROM category
         WHERE external_id = echange_sites.cat_id2)
    )

  FROM ereferer_prod_2.echange_sites;

DELETE FROM exchange_site_category WHERE category_id = (SELECT id FROM category WHERE external_id = 99999);
DELETE FROM category WHERE external_id = 99999;
