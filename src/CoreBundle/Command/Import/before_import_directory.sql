UPDATE ereferer.directory set name = TRIM(TRAILING '/' FROM name);

DELETE d1
FROM directory as d1 INNER JOIN directory as d2 #doesn't work in phpmyadmin
WHERE
  d1.id < d2.id AND d1.name = d2.name;

UPDATE ereferer.directory INNER JOIN ereferer_prod_2.annuaires ON directory.name = TRIM(TRAILING '/' FROM annuaires.annuaire)
set external_id = annuaires.id;
