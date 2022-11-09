#RUT THIS IF YOU WANT TO MERGE 2 DATABASES
UPDATE ereferer.fos_user set external_id = (SELECT id FROM ereferer_prod_2.utilisateurs WHERE fos_user.email = utilisateurs.email);

INSERT IGNORE INTO ereferer.fos_user (
  country,
  username,
  username_canonical,
  email,
  email_canonical,
  enabled,
  last_login,
  full_name,
  phone,
  address,
  zip,
  created_at,
  spending,
  web_site,
  city,
  vat_number,
  password,
  roles,
  contract_accepted,
  project_hidden_editor,
  bonus_projects,
  contract_drafting_accepted,
  trusted,
  balance,
  credit,
  show_credit,
  external_id
)
  SELECT
    country,
    email,
    email,
    email, #username has dublicates
    email,
    active,
    FROM_UNIXTIME(lastlogin),
    CONCAT(nom, " ", prenom),
    telephone,
    adresse,
    codepostal,
    created,
    frais,
    siteweb,
    ville,
    numtva,
    "123",
    (CASE
     WHEN typeutilisateur = 3
       THEN (CASE
             WHEN annuaire_writer = 1 AND redaction_writer = 1
               THEN "a:1:{i:0;s:11:\"ROLE_WRITER\";}"
             WHEN annuaire_writer = 1
               THEN "a:1:{i:0;s:22:\"ROLE_WRITER_NETLINKING\";}"
             WHEN redaction_writer = 1
               THEN "a:1:{i:0;s:23:\"ROLE_WRITER_COPYWRITING\";}"
             END)
     WHEN typeutilisateur = 1
       THEN "a:1:{i:0;s:16:\"ROLE_SUPER_ADMIN\";}"
     WHEN typeutilisateur = 2
       THEN "a:1:{i:0;s:17:\"ROLE_WRITER_ADMIN\";}"
     WHEN typeutilisateur = 4
       THEN "a:1:{i:0;s:14:\"ROLE_WEBMASTER\";}"
     END),
    contractAccepted,
    writers_choosing,
    bonus_projects,
    redaction_contract_accepted,
    trusted,
    (solde + credit * 10),
    credit,
    IF(credit > 0, true, false),
    id

  FROM ereferer_prod_2.utilisateurs;

CREATE INDEX external_id_index ON fos_user (external_id) USING BTREE;
UPDATE fos_user set country = (CASE
                               WHEN country = "France" THEN "FR"
                               WHEN country = "Belgique" THEN "BE"
                               WHEN country = "Suisse" THEN "CH"
                               WHEN country = "Martinique" THEN "MQ"
                               WHEN country = "Thailande" THEN "TH​"
                               WHEN country = "Madagascar" THEN "MG​"
                               WHEN country = "Hong_Kong" THEN "HK"
                               WHEN country = "Espagne" THEN "ES"
                               WHEN country = "Canada" THEN "CA​"
                               WHEN country = "Luxembourg" THEN "LU"
                               WHEN country = "Maroc" THEN "MA​"
                               WHEN country = "Afghanistan" THEN "AF"
                               WHEN country = "Andorre" THEN "AD"
                               WHEN country = "Singapour" THEN "SG​"
                               WHEN country = "Allemagne" THEN "DE"
                               WHEN country = "Coree_du_Sud" THEN "KR"
                               WHEN country = "Reunion" THEN "RE"
                               WHEN country = "Togo" THEN "TG"
                               WHEN country = "Tunisie" THEN "TN"
                               WHEN country = "Etats_Unis" THEN "US​"
                               WHEN country = "Mayotte" THEN "YT"
                               WHEN country = "Chine" THEN "CN"
                               WHEN country = "Pologne" THEN "PL"
                               WHEN country = "Benin" THEN "BJ"
                               WHEN country = "Guinee" THEN "GN"
                               WHEN country = "Estonie" THEN "EE"
                               WHEN country = "Panama" THEN "PA"
                               WHEN country = "Emirats_Arabes_Unis" THEN "AE​"
                               WHEN country = "Royaume_Uni" THEN "GB​"
                               WHEN country = "Algerie" THEN "DZ"
                               WHEN country = "Vietnam" THEN "VN"
                               WHEN country = "Moldavie" THEN "MD"
                               WHEN country = "Bielorussie" THEN "BY​"
                               WHEN country = "Turquie" THEN "TR​"
                               WHEN country = "Australie" THEN "AU​"
                               WHEN country = "Guadeloupe" THEN "GP"
                               WHEN country = "Israel" THEN "IL"
                               WHEN country = "Costa_Rica" THEN "CR"
                               WHEN country = "Roumanie" THEN "RO"
                               WHEN country = "Nouvelle_Caledonie" THEN "NC"
                               WHEN country = "Portugal" THEN "PT"
                               WHEN country = "Maurice" THEN "MU"
                               WHEN country = "Russie" THEN "RU"
                               WHEN country = "Congo" THEN "CD​"
                               WHEN country = "Cameroun" THEN "CM"
                               WHEN country = "Senegal" THEN "SN"
                               WHEN country = "Georgie" THEN "GE​"
                               WHEN country = "Cote_d_Ivoire" THEN "CI​"
                               WHEN country = "Turkmenistan" THEN "TM"
                               WHEN country = "Bresil" THEN "BR"
                               WHEN country = "Indonesie" THEN "ID"
                               WHEN country = "Burkina_Faso" THEN "BF"
                               WHEN country = "Gibraltar" THEN "GI"
                               WHEN country = "Italie" THEN "IT"
                               WHEN country = "Republique_Tcheque" THEN "CZ"
                               WHEN country = "Ghana" THEN "GH"
                               WHEN country = "Canaries" THEN "ES-CN"
                               WHEN country = "Bulgarie" THEN "BG"
                               WHEN country = "Colombie" THEN "CO"
                               WHEN country = "Malte" THEN "MT"
                               WHEN country = "Cambodge" THEN "KH"
                               WHEN country = "Guyane_Francaise" THEN "GF"
                               WHEN country = "Jordanie" THEN "JO"
                               WHEN country = "Burundi" THEN "BI"
                               WHEN country = "Haiti" THEN "HT"
                               WHEN country = "Inde" THEN "IN"
                               WHEN country = "Guatemala" THEN "GT"
                               WHEN country = "Monaco" THEN "MC"
                               WHEN country = "Japon" THEN "JP"
                               WHEN country = "Mexique" THEN "MX"
                               WHEN country = "Uruguay" THEN "UY"
                               WHEN country = "Pays_Bas" THEN "NL"
                               WHEN country = "Ukraine" THEN "UA"
                               WHEN country = "Lituanie" THEN "LT"
                               WHEN country = "Guernesey" THEN "GG"
                               WHEN country = "Irlande" THEN "IE"
                               ELSE country
                               END)
  WHERE country IS NOT NULL AND country != "";

#DON'T USE THIS UPDATE ON PROD
#CHANGE THE WHERE IN THIS SQL
UPDATE fos_user set email = CONCAT(id, "info@infffos.com"), email_canonical = CONCAT(id, "info@infffos.com") WHERE external_id IS NOT NULL;
#DON'T USE THIS ON PROD
