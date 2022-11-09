<?php

namespace CoreBundle\DataFixtures\ORM;

use CoreBundle\Entity\UserSetting;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use CoreBundle\Entity\Settings;
use DoctrineExtensions\Tests\Entities\Set;

/**
 * Class LoadSettings
 *
 * @package CoreBundle\DataFixtures\ORM
 */
class LoadSettings extends AbstractFixture implements FixtureInterface
{

    private $settings = [
        ['name' => Settings::DEFAULT_WORDS_PER_DAY,                 'identificator' => Settings::DEFAULT_WORDS_PER_DAY,                         'value' => 500],
        ['name' => Settings::WORDS_PER_DAY,                         'identificator' => Settings::WORDS_PER_DAY,                                 'value' => 5000],
        ['name' => Settings::PRICE_PER_100_WORDS,                   'identificator' => Settings::PRICE_PER_100_WORDS,                           'value' => 1.6],
        ['name' => Settings::WRITER_PRICE_PER_100_WORDS,            'identificator' => Settings::WRITER_PRICE_PER_100_WORDS,                    'value' => 0.75],
        ['name' => Settings::CORRECTOR_PRICE_PER_100_WORDS,         'identificator' => Settings::CORRECTOR_PRICE_PER_100_WORDS,                 'value' => 0.25],
        ['name' => Settings::REDUCED_CORRECTOR_PRICE_PER_100_WORDS, 'identificator' => Settings::REDUCED_CORRECTOR_PRICE_PER_100_WORDS,         'value' => 0.15],
        ['name' => Settings::PRICE_PER_IMAGE,                       'identificator' => Settings::PRICE_PER_IMAGE,                               'value' => 0.35],
        ['name' => Settings::WRITER_PRICE_PER_IMAGE,                'identificator' => Settings::WRITER_PRICE_PER_IMAGE,                        'value' => 0.15],
        ['name' => Settings::EXPRESS_RATE,                          'identificator' => Settings::EXPRESS_RATE,                                  'value' => 0.50],
        ['name' => Settings::WRITER_EXPRESS_RATE,                   'identificator' => Settings::WRITER_EXPRESS_RATE,                           'value' => 0.25],
        ['name' => Settings::CORRECTOR_EXPRESS_RATE,                'identificator' => Settings::CORRECTOR_EXPRESS_RATE,                        'value' => 0.05],
        ['name' => Settings::DEFAULT_DIRECTORY_ZERO_WORDS_COUNT,    'identificator' => Settings::DEFAULT_DIRECTORY_ZERO_WORDS_COUNT,            'value' => 100],
        ['name' => 'Price on homepage',                             'identificator' => Settings::PRICE_ON_HOMEPAGE,                             'value' => 1.85],
        ['name' => 'Invoice EURL',                                  'identificator' => Settings::INVOICE_EURL,                                  'value' => 'EREFERER'],
        ['name' => 'Invoice SIRET',                                 'identificator' => Settings::INVOICE_SIRET,                                 'value' => '52281558800010'],
        ['name' => 'Invoice First Name',                            'identificator' => Settings::INVOICE_FIRST_NAME,                            'value' => 'Emmanuel'],
        ['name' => 'Invoice Last Name',                             'identificator' => Settings::INVOICE_LAST_NAME,                             'value' => 'Higel'],
        ['name' => 'Invoice Headquarters Address',                  'identificator' => Settings::INVOICE_HEADQUARTES_ADDRESS,                   'value' => '26 Rue des ifs'],
        ['name' => 'Invoice Area Address',                          'identificator' => Settings::INVOICE_AREA_ADDRESS,                          'value' => 'La chapelle sur Erdre'],
        ['name' => 'Invoice Postal Code',                           'identificator' => Settings::INVOICE_POSTAL_CODE,                           'value' => '44240'],
        ['name' => 'Invoice Country',                               'identificator' => Settings::INVOICE_COUNTRY,                               'value' => 'France'],
        ['name' => 'Invoice IBAN',                                  'identificator' => Settings::INVOICE_IBAN,                                  'value' => 'FR76 3000 3020 3300 0203 1287 679'],
        ['name' => 'Invoice BIC/SWIFT',                             'identificator' => Settings::INVOICE_BIC_SWIFT,                             'value' => 'SOGEFRPP'],
        ['name' => 'Invoice VAT number',                            'identificator' => Settings::INVOICE_VAT_NUMBER,                            'value' => 'FR38522815588'],
        ['name' => 'Default EU VAT',                                'identificator' => 'default_eu_vat',                                        'value' => '20'],
        ['name' => 'tps_reac_webmaster',                            'identificator' => 'tps_reac_webmaster',                                    'value' => '15'],
        ['name' => 'You like writers cost',                         'identificator' => Settings::PRICE_YOU_LIKE_WRITERS,                        'value' => 0.25],
        ['name' => 'You like writers cost for writer',              'identificator' => Settings::WRITER_PRICE_YOU_LIKE_WRITERS,                 'value' => 0.15],
        ['name' => 'Top writers cost',                              'identificator' => Settings::PRICE_TOP_WRITERS,                             'value' => 0.50],
        ['name' => 'Top writers cost for writer',                   'identificator' => Settings::WRITER_PRICE_TOP_WRITERS,                      'value' => 0.30],
        ['name' => 'Best writers cost',                             'identificator' => Settings::PRICE_BEST_WRITERS,                            'value' => 1.0],
        ['name' => 'Best writers cost for writer',                  'identificator' => Settings::WRITER_PRICE_BEST_WRITERS,                     'value' => 0.60],
        ['name' => 'Exchange COMMISSION percent',                   'identificator' => Settings::COMMISSION_PERCENT,                            'value' => 10],
        ['name' => 'Exchange WITHDRAW percent',                     'identificator' => Settings::WITHDRAW_PERCENT,                              'value' => 15],
        ['name' => 'Webmaster Tariff',                              'identificator' => Settings::TARIFF_WEB,                                    'value' => 1.85],
        ['name' => 'Remuneration per directory',                    'identificator' => 'remuneration',                                          'value' => 0.6],
        ['name' => 'Minimum amount of withdraw ',                   'identificator' => Settings::MINIMUM_WITHDRAW,                              'value' => 100],
        ['name' => 'Amount of withdrawals per month',               'identificator' => Settings::WITHDRAW_PER_MONTH,                            'value' => 1],
        ['name' => 'More information of blog plugin',               'identificator' => Settings::PLUGIN_MORE_INFORMATION,                       'value' => 'http://www.votons.info/explication-en-details-du-systeme-politique-aux-etats-unis/'],
        ['name' => 'Webmaster additional pay',                      'identificator' => Settings::WEBMASTER_ADDITIONAL_PAY,                      'value' => 10],
        ['name' => 'Price for meta desctiption',                    'identificator' => Settings::PRICE_FOR_META_DESCRIPTION,                    'value' => 0.35],
        ['name' => 'Writer reward for meta description',            'identificator' => Settings::WRITER_REWARD_FOR_META_DESCRIPTION,            'value' => 0.2],
        ['name' => 'prix_achat_credit',                             'identificator' => 'prix_achat_credit',                                     'value' => 10],

        ['name' => 'Default value for user settings: The frequency of e-mail notifications of new proposal', 'identificator' => UserSetting::PREFIX_FOR_SETTING . UserSetting::NOTIFICATION_PROPOSAL_FREQUENCY,  'value' => 1],

        ['name' => 'Default permission status: Can manage Copywriting project', 'identificator' => UserSetting::PREFIX_FOR_SETTING . UserSetting::PERMISSION_MANAGE_COPYWRITING_PROJECT,  'value' => 0],
        ['name' => 'Default permission status: Can manage Netlinking project', 'identificator' => UserSetting::PREFIX_FOR_SETTING . UserSetting::PERMISSION_MANAGE_NETLINKING_PROJECT,  'value' => 0],
        ['name' => 'Default permission status: Can manage client user', 'identificator' => UserSetting::PREFIX_FOR_SETTING . UserSetting::PERMISSION_MANAGE_WEBMASTER_USER,  'value' => 0],
        ['name' => 'Default permission status: Can manage writer user', 'identificator' => UserSetting::PREFIX_FOR_SETTING . UserSetting::PERMISSION_MANAGE_WRITER_USER,  'value' => 0],
        ['name' => 'Default permission status: Can answer message', 'identificator' => UserSetting::PREFIX_FOR_SETTING . UserSetting::PERMISSION_ANSWER_MESSAGE,  'value' => 0],
        ['name' => 'Default permission status: Can manage earning', 'identificator' => UserSetting::PREFIX_FOR_SETTING . UserSetting::PERMISSION_MANAGE_EARNING,  'value' => 0],
        ['name' => 'Default email', 'identificator' => Settings::EMAIL,  'value' => 'emmanuelhigel@gmail.com'],
        ['name' => 'Default email language', 'identificator' => Settings::EMAIL_LANGUAGE,  'value' => 'fr'],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->settings as $setting) {
            $entity = $this->findOrCreateEntity($setting['identificator'], $manager);

            if (!$manager->contains($entity)) {
                $entity
                    ->setName($setting['name'])
                    ->setIdentificator($setting['identificator'])
                    ->setValue($setting['value']);

                $manager->persist($entity);
            }
        }

        $manager->flush();
    }

    /**
     * @param string        $identificator
     * @param ObjectManager $manager
     *
     * @return Settings
     */
    protected function findOrCreateEntity($identificator, ObjectManager $manager)
    {
        return $manager->getRepository(Settings::class)->findOneBy(['identificator' => $identificator]) ?: new Settings();
    }
}
