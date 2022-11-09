<?php

namespace CoreBundle\DataFixtures\ORM;

use CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use CoreBundle\Entity\EmailTemplates;

/**
 * Class LoadEmailTemplatesData
 *
 * @package CoreBundle\DataFixtures\ORM
 */
class LoadEmailTemplatesData extends AbstractFixture implements FixtureInterface
{
    private $templates = [
        [
            'name' => 'Notification: New proposal',
            'identificator' => User::NOTIFICATION_NEW_PROPOSAL,
            'subject' => 'You received a new proposal in the article exchange module on Ereferer',
            'emailContent' => '<p>Hello %userName%!</p>\r\n<p><span id="result_box" class="" lang="en"><span class="">You received a new proposal in the article exchange module:</span></span></p>',
            'language' => 'en',
        ],
        [
            'name' => 'Notification: requested modification for exchange proposal result',
            'identificator' => User::NOTIFICATION_CHANGE_PROPOSAL,
            'subject' => 'You received a new proposal in the article exchange module on Ereferer',
            'emailContent' => 'Hello %userName%, <br/> <br/>\nYou received a new proposal in the article exchange module: <br/>\n<a href="%link%">%link%</a><br/><br/>\nSincerely, <br/> Emmanuel',
            'language' => 'en',
        ],
        [
            'name' => 'Notification: Webmaster starts new netlinking project',
            'identificator' => User::NOTIFICATION_START_NEW_NETLINKING_PROJECT,
            'subject' => 'Webmaster %userName% has started new netlinking project',
            'emailContent' => 'Veuillez vous connecter pour affecter des r&eacute;f&eacute;renceur &agrave; ces projets svp.',
            'language' => 'en',
        ],
        [
            'name' => 'Notification: Link found',
            'identificator' => User::NOTIFICATION_BACKLINK_FOUND,
            'subject' => 'Your link on %url% was found',
            'emailContent' => 'Hello %userName%,<br/> <br/> The directory %url% has validated your link. You can see it at the following address: <br/> %backlink% <br/> <br/> Sincerely, <br/> Emmanuel',
            'language' => 'en',
        ],
//        [
//            'name' => 'Notification: Zero amount',
//            'identificator' => User::NOTIFICATION_ZERO_AMOUNT,
//            'subject' => 'Balance is low',
//            'emailContent' => 'Hello %userName%, <br/> <br/> Your balance is low. <br/> Replenish balance: %replenish_link% <br/> <br/> Sincerely, <br/> Emmanuel',
//            'language' => 'en',
//        ],
        [
            'name' => 'Notification: Netlinking project completed',
            'identificator' => User::NOTIFICATION_NETLINKING_PROJECT_FINISHED,
            'subject' => 'Netlinking project completed',
            'emailContent' => 'Hello %userName%, <br/> <br/> Your netlinking project has been completed successfully. <br/> Link to project: <a href="%baseUrl%%project_link%">%baseUrl%%project_link%</a> <br/> <br/> Sincerely, <br/> Emmanuel',
            'language' => 'en',
        ],
        [
            'name' => 'Notification: Article ready',
            'identificator' => User::NOTIFICATION_ARTICLE_READY,
            'subject' => 'Article ready',
            'emailContent' => 'Hello %userName%, <br/> <br/> Article has been ready. <br/> Link to article: <a href="%baseUrl%%article_link%">%baseUrl%%article_link%</a><br/> <br/> Sincerely, <br/> Emmanuel',
            'language' => 'en',
        ],
        [
            'name' => 'Letter: Confirmation of registration',
            'identificator' => User::LETTER_CONFIRMATION_EMAIL,
            'subject' => 'Confirmation of registration',
            'emailContent' => 'Hello %userName%, <br/> <br/> To confirm registration, follow this link: <a href="%baseUrl%%confirmationUrl%">%baseUrl%%confirmationUrl%</a> <br/> <br/> Sincerely, <br/> Emmanuel',
            'language' => 'en',
        ],
        [
            'name' => 'Letter: Reset password',
            'identificator' => User::LETTER_RESET_PASSWORD,
            'subject' => 'Reset password',
            'emailContent' => 'Hello %userName%, <br/> <br/> To reset your password, follow this link: <a href="%baseUrl%%confirmationUrl%">%baseUrl%%confirmationUrl%</a> <br/> <br/> Sincerely, <br/> Emmanuel',
            'language' => 'en',
        ],
        [
            'name' => 'Notification: New message',
            'identificator' => User::LETTER_NEW_MESSAGE,
            'subject' => 'You\'ve got mail',
            'emailContent' => 'Hello %userName%, <br/> <br/> You received a new message from %fromName%. <br/> Link to message: <a href="%baseUrl%%messageLink%">%baseUrl%%messageLink%</a> <br/> <br/> Sincerely, <br/> Emmanuel',
            'language' => 'en',
        ],
        [
            'name' => 'Notification: New message with content',
            'identificator' => User::LETTER_NEW_MESSAGE_WITH_CONTENT,
            'subject' => 'You\'ve got mail',
            'emailContent' => 'Hello %userName%, <br/> <br/> You received a new message from %fromName%: <br/><br/>%message%<br/><br/> Link to message: <a href="%baseUrl%%messageLink%">%baseUrl%%messageLink%</a> <br/> <br/> Sincerely, <br/> Emmanuel',
            'language' => 'en',
        ],
        [
            'name' => 'Notification: A reminder of the new proposal',
            'identificator' => User::NOTIFICATION_NEW_PROPOSAL_REMINDER,
            'subject' => 'Un rappel de la nouvelle proposition',
            'emailContent' => 'You have suggestions for the exchange of articles. <br/><br/>Link to page: <a href="%baseUrl%%link%">%baseUrl%%link%</a><br><br> %proposalList%',
            'language' => 'fr',
        ],
        [
            'name' => 'Notify developers about critical error',
            'identificator' => EmailTemplates::NOTIFICATION_CRITICAL_ERROR,
            'subject' => 'CRITICAL ERROR',
            'emailContent' => "We got critical error at production server <br/> Message: %message% <br/> File: %file%",
            'language' => 'en',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->templates as $template) {
            $entity = $this->findOrCreateEntity($template['identificator'], $manager);

            if (!$manager->contains($entity)) {
                $entity
                    ->setName($template['name'])
                    ->setIdentificator($template['identificator'])
                    ->setSubject($template['subject'])
                    ->setEmailContent($template['emailContent'])
                    ->setLanguage($template['language']);
            }

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @param string        $identificator
     * @param ObjectManager $manager
     *
     * @return EmailTemplates
     */
    protected function findOrCreateEntity($identificator, ObjectManager $manager)
    {
        return $manager->getRepository(EmailTemplates::class)->findOneBy(['identificator' => $identificator]) ?: new EmailTemplates();
    }
}
