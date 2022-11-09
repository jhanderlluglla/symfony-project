<?php

namespace CoreBundle\DataFixtures\Test;

use CoreBundle\DataFixtures\ORM\LoadSettings;
use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingArticleRating;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineExtensions\Query\Mysql\Date;


class LoadCopywritingProjectData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $copywritingProject = new CopywritingProject();
        $copywritingProject->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingProject->setTitle("Project #1");
        $copywritingProject->setDescription("Project #1");
        $copywritingProject->setLanguage(Language::EN);

        $manager->persist($copywritingProject);

        $copywritingOrder = new CopywritingOrder();
        $copywritingOrder->setTitle('P#1-O#1: submitted_to_admin');
        $copywritingOrder->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingOrder->setCopywriter($this->getReference('user-test-writer-1'));
        $copywritingOrder->setStatus(CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN);
        $copywritingOrder->setAmount(10);
        $copywritingOrder->setImagesPerArticleFrom(0);
        $copywritingOrder->setImagesPerArticleTo(3);
        $copywritingOrder->setWordsNumber(200);
        $copywritingOrder->setCreatedAt(new \DateTime('-2 day'));
        $copywritingOrder->setTakenAt(new \DateTime('-1 day'));
        $copywritingOrder->setReadyForReviewAt(new \DateTime());

        $manager->persist($copywritingOrder);

        $copywritingProject->addOrder($copywritingOrder);

        $article = new CopywritingArticle();
        $article->setOrder($copywritingOrder);
        $article->setText('text text text text text text text text text text text text text text text');

        $manager->persist($article);

        $copywritingArticleRating = new CopywritingArticleRating();
        $copywritingArticleRating->setValue(true);
        $copywritingArticleRating->setOrder($copywritingOrder);
        $manager->persist($copywritingArticleRating);

        //
        // Project 2
        //

        $copywritingProject = new CopywritingProject();
        $copywritingProject->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingProject->setTitle("Project #2");
        $copywritingProject->setDescription("Project #2");
        $copywritingProject->setLanguage(Language::EN);

        $manager->persist($copywritingProject);

        $copywritingOrder = new CopywritingOrder();
        $copywritingOrder->setTitle('P#2-O#2: submitted_to_admin (image:1:0:2)');
        $copywritingOrder->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingOrder->setCopywriter($this->getReference('user-test-writer-1'));
        $copywritingOrder->setStatus(CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN);
        $copywritingOrder->setAmount(10);
        $copywritingOrder->setImagesPerArticleFrom(0);
        $copywritingOrder->setImagesPerArticleTo(2);
        $copywritingOrder->setWordsNumber(200);
        $copywritingOrder->setCreatedAt(new \DateTime('-2 day'));
        $copywritingOrder->setTakenAt(new \DateTime('-1 day'));
        $copywritingOrder->setReadyForReviewAt(new \DateTime());

        $manager->persist($copywritingOrder);

        $copywritingProject->addOrder($copywritingOrder);

        $article = new CopywritingArticle();
        $article->setOrder($copywritingOrder);
        $article->setImagesByWriter(['test.jpg']);
        $article->setText('<div>text text text text text text text text text text text text text text text <img src="test.jpg" /></div>');

        $manager->persist($article);

        $copywritingArticleRating = new CopywritingArticleRating();
        $copywritingArticleRating->setValue(true);
        $copywritingArticleRating->setOrder($copywritingOrder);
        $manager->persist($copywritingArticleRating);

        //
        // Project 3
        //

        $copywritingProject = new CopywritingProject();
        $copywritingProject->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingProject->setTitle("Project #3");
        $copywritingProject->setDescription("Project #3");
        $copywritingProject->setLanguage(Language::EN);

        $manager->persist($copywritingProject);

        $copywritingOrder = new CopywritingOrder();
        $copywritingOrder->setTitle('P#3-O#1: submitted_to_admin');
        $copywritingOrder->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingOrder->setCopywriter($this->getReference('user-test-writer-1'));
        $copywritingOrder->setStatus(CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN);
        $copywritingOrder->setAmount(10);
        $copywritingOrder->setImagesPerArticleFrom(0);
        $copywritingOrder->setImagesPerArticleTo(2);
        $copywritingOrder->setWordsNumber(200);
        $copywritingOrder->setCreatedAt(new \DateTime('-4 day'));
        $copywritingOrder->setTakenAt(new \DateTime('-1 day'));
        $copywritingOrder->setReadyForReviewAt(new \DateTime());

        $copywritingProject->addOrder($copywritingOrder);
        $manager->persist($copywritingOrder);

        $article = new CopywritingArticle();
        $article->setOrder($copywritingOrder);
        $article->setImagesByWriter(['test.jpg']);
        $article->setText(self::generateRandomText(202) . '<img src="test.jpg" />');

        $manager->persist($article);

        $copywritingArticleRating = new CopywritingArticleRating();
        $copywritingArticleRating->setValue(true);
        $copywritingArticleRating->setOrder($copywritingOrder);
        $manager->persist($copywritingArticleRating);

        $copywritingOrder = new CopywritingOrder();
        $copywritingOrder->setTitle('P#3-O#2: submitted_to_admin');
        $copywritingOrder->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingOrder->setCopywriter($this->getReference('user-test-writer-1'));
        $copywritingOrder->setStatus(CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN);
        $copywritingOrder->setAmount(10);
        $copywritingOrder->setImagesPerArticleFrom(0);
        $copywritingOrder->setImagesPerArticleTo(2);
        $copywritingOrder->setWordsNumber(200);
        $copywritingOrder->setCreatedAt(new \DateTime('-5 day'));
        $copywritingOrder->setTakenAt(new \DateTime('-3 day'));
        $copywritingOrder->setReadyForReviewAt(new \DateTime());
        $copywritingOrder->setExpress(true);

        $copywritingProject->addOrder($copywritingOrder);
        $manager->persist($copywritingOrder);

        $article = new CopywritingArticle();
        $article->setOrder($copywritingOrder);
//        $article2->setImagesByWriter([]);
        $article->setText(self::generateRandomText(220));

        $manager->persist($article);

        $copywritingArticleRating = new CopywritingArticleRating();
        $copywritingArticleRating->setValue(true);
        $copywritingArticleRating->setOrder($copywritingOrder);
        $manager->persist($copywritingArticleRating);

        //
        // Project 4
        //

        $copywritingProject = new CopywritingProject();
        $copywritingProject->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingProject->setTitle("Project #4");
        $copywritingProject->setDescription("Project #4");
        $copywritingProject->setLanguage(Language::EN);

        $manager->persist($copywritingProject);

        $copywritingOrder = new CopywritingOrder();
        $copywritingOrder->setTitle('P#4-O#1: submitted_to_admin (metaDescription:1)');
        $copywritingOrder->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingOrder->setCopywriter($this->getReference('user-test-writer-1'));
        $copywritingOrder->setStatus(CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN);
        $copywritingOrder->setAmount(10);
        $copywritingOrder->setImagesPerArticleFrom(0);
        $copywritingOrder->setImagesPerArticleTo(0);
        $copywritingOrder->setWordsNumber(200);
        $copywritingOrder->setCreatedAt(new \DateTime('-2 day'));
        $copywritingOrder->setTakenAt(new \DateTime('-1 day'));
        $copywritingOrder->setReadyForReviewAt(new \DateTime());

        $manager->persist($copywritingOrder);

        $copywritingProject->addOrder($copywritingOrder);

        $article = new CopywritingArticle();
        $article->setOrder($copywritingOrder);
        $article->setText('<div>text text text text text text text text text text text text text text text</div>');

        $manager->persist($article);

        $copywritingArticleRating = new CopywritingArticleRating();
        $copywritingArticleRating->setValue(true);
        $copywritingArticleRating->setOrder($copywritingOrder);
        $manager->persist($copywritingArticleRating);

        $copywritingOrder = new CopywritingOrder();
        $copywritingOrder->setTitle('P#4-O#2: new 100 words');
        $copywritingOrder->setCustomer($this->getReference('user-test-webmaster-1'));
        $copywritingOrder->setStatus(CopywritingOrder::STATUS_WAITING);
        $copywritingOrder->setWordsNumber(100);

        $manager->persist($copywritingOrder);

        $copywritingProject->addOrder($copywritingOrder);

        $manager->persist($article);

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [LoadUserData::class, LoadSettings::class];
    }

    public static function generateRandomText($numberOfWords)
    {
        $result = "";
        for($i=0; $i < $numberOfWords; $i++){
            $result .= substr(str_shuffle(MD5(microtime())), 0, rand(0, 10)) . " ";
        }
        return $result;
    }
}
