<?php

namespace CoreBundle\DataFixtures\ORM;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\StaticPage;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadStaticPageData extends AbstractFixture implements FixtureInterface
{

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            [
                'id' => StaticPage::PAGE_HELP_WEBMASTER,
                'name' => 'Webmaster Help Page',
                'page_content' => '<p>Hello my dear friend!</p><p>&nbsp;</p><h1>H1 test</h1>',
                'language' => Language::EN,
            ]
        ];

        foreach ($data as $dataPage) {
            if ($this->isExists($dataPage['id'], $manager)) {
                continue;
            }

            $page = new StaticPage();
            $page
                ->setLanguage($dataPage['language'])
                ->setIdentificator($dataPage['id'])
                ->setName($dataPage['name'])
                ->setPageContent($dataPage['page_content'])
            ;

            $manager->persist($page);
        }

        $manager->flush();
    }

    /**
     * @param string $id
     * @param ObjectManager $manager
     *
     * @return StaticPage
     */
    protected function isExists($id, ObjectManager $manager)
    {
        return $manager->getRepository(StaticPage::class)->findOneBy(['identificator' => $id]);
    }
}
