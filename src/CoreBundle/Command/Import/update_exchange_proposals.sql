UPDATE exchange_proposition
SET article_author_type = "writer"
WHERE id IN (SELECT temporary_proposals.id
             FROM (SELECT * FROM exchange_proposition) as temporary_proposals
               INNER JOIN copywriting_order ON copywriting_order.exchange_proposition_id = temporary_proposals.id
             WHERE temporary_proposals.article_author_type = "unknown_type");


UPDATE exchange_proposition
SET article_author_type = "buyer"
WHERE document_link IS NOT NULL AND article_author_type = "unknown_type";


UPDATE exchange_proposition
SET article_author_type = "webmaster"
WHERE article_author_type = "unknown_type";
