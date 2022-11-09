<?php

namespace Tests\CoreBundle;

use CoreBundle\DataFixtures\Test\LoadCopywritingProjectData;
use CoreBundle\DataFixtures\ORM as ORM;
use CoreBundle\DataFixtures\Test\LoadUserData;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\User;
use CoreBundle\Exceptions\UnknownNotificationName;
use Tests\AbstractTest;
use Tests\ParamWrapper;

class UserTest extends AbstractTest
{
    public function testChangeNotificationSettings()
    {
        $user = new User();
        $user->setNotificationSettings(bindec('10111')); // 10111 -> 23

        $user->setNotificationEnabled(User::NOTIFICATION_BACKLINK_FOUND, User::NOTIFICATION_OFF); // 00111 -> 7
        self::assertEquals($user->getNotificationSettings(), 7);
        self::assertEquals($user->isNotificationEnabled(User::NOTIFICATION_BACKLINK_FOUND), User::NOTIFICATION_OFF);

        $user->setNotificationEnabled(User::NOTIFICATION_BACKLINK_FOUND, User::NOTIFICATION_ON); // 10111 -> 23
        self::assertEquals($user->getNotificationSettings(), 23);
        self::assertEquals($user->isNotificationEnabled(User::NOTIFICATION_BACKLINK_FOUND), User::NOTIFICATION_ON);

        $user->setNotificationEnabled(User::NOTIFICATION_ARTICLE_READY, User::NOTIFICATION_ON); // 110111 -> 55
        self::assertEquals($user->getNotificationSettings(), 55);
        self::assertEquals($user->isNotificationEnabled(User::NOTIFICATION_ARTICLE_READY), User::NOTIFICATION_ON);

        $user->setNotificationEnabled(User::NOTIFICATION_NEW_PROPOSAL, User::NOTIFICATION_OFF); // 110011 -> 51
        self::assertEquals($user->getNotificationSettings(), 51);
        self::assertEquals($user->isNotificationEnabled(User::NOTIFICATION_NEW_PROPOSAL), User::NOTIFICATION_OFF);

        $this->expectException(UnknownNotificationName::class);
        $user->setNotificationEnabled('Unknown notification name', User::NOTIFICATION_ON);
    }

    /**
     * @dataProvider notificationArticleReadyProvider
     *
     * @param CopywritingOrder $copywritingOrder
     * @param $notificationEnabled
     * @throws \Exception
     */
    public function testNotificationArticleReady($copywritingOrder, $notificationEnabled)
    {
        $fixtures = [
            LoadUserData::class,
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            LoadCopywritingProjectData::class,
        ];

        $this->loadFixtures($fixtures);

        if (is_array($copywritingOrder)) {
            /** @var CopywritingOrder $copywritingOrder */
            $copywritingOrder = $this->getObjectOf(CopywritingOrder::class, $copywritingOrder);
        }

        $this->setUser($copywritingOrder->getCustomer());

        $copywritingOrder->getCustomer()->setNotificationEnabled(User::NOTIFICATION_ARTICLE_READY, $notificationEnabled);

        $this->enableMessageLogger();

        $workflowCopywritingOrder = $this->container()->get('state_machine.copywriting_order');
        $workflowCopywritingOrder->apply($copywritingOrder, CopywritingOrder::TRANSITION_SUBMIT_TO_WEBMASTER);

        if ($notificationEnabled === User::NOTIFICATION_ON) {
            $this->assertSame(1, $this->messageLogger()->countMessages(), 'The letter has not been sent');

            $message = $this->getMessage();
            $this->assertArrayHasKey($copywritingOrder->getCustomer()->getEmail(), $message->getTo());
        } else {
            $this->assertSame(0, $this->messageLogger()->countMessages(), 'Message sent with notification disabled');
        }
    }

    public function notificationArticleReadyProvider()
    {
        return [
            [['title' => 'P#1-O#1: submitted_to_admin'], User::NOTIFICATION_ON],
            [['title' => 'P#1-O#1: submitted_to_admin'], User::NOTIFICATION_OFF],
        ];
    }

    /**
     * @dataProvider registrationDataProvider
     *
     * @param $success
     * @param $data
     * @param array $contains
     */
    public function testRegistration($success, $data, $contains = [])
    {
        $fixtures = [
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            LoadUserData::class,
        ];

        $this->loadFixtures($fixtures);

        $this->enableMessageLogger();

        $response = $this->sendForm('/register/', 'fos_user_registration_form', $data);

        foreach ($contains as $contain) {
            self::assertContains($contain, $response);
        }

        if (!$success) {
            self::assertTrue(true);
            return;
        }

        $user = $this->getObjectOf(User::class, ['email' => $data['email']], false);

        self::assertEquals($user->isEnabled(), false, 'The user after registration must confirm his e-mail');

        $message = $this->getMessage();

        if (!$message) {
            self::assertFail('Registration confirmation letter was not sent');
        }

        self::assertContains($user->getConfirmationToken(), $message->getBody(), 'Token not found in the letter');

        if (!preg_match('~://[a-z.]+(/.*?)"~', $message->getBody(), $confirmUrl)) {
            $this->assertFail('Email verification link not found in message body');
        }

        $this->sendGet($confirmUrl[1]);

        self::assertEquals($user->isEnabled(), true, 'After clicking on the activation link, the user has not been activated');
    }

    public function registrationDataProvider()
    {
        $baseData = [
            'fullName' => 'Test',
            'email' => 'test@test.ua',
            'plainPassword' => [
                'first' => '123',
                'second' => '123',
            ],
            'country' => 'FR',
            'city' => 'Test',
            'address' => 'Test',
            'phone' => '012345678910',
        ];

        return [
            [true, $baseData, ['/register/check-email']],
            [false, ['email' => 'testtest.ua'] + $baseData, ['The email is not valid']],
            [false, ['email' => 'webmaster-1@test.com'] + $baseData, ['The email is already used']],
            [false, ['plainPassword' => ['first' => '123', 'second' => '321']] + $baseData, ['The entered passwords don&#039;t match.']],
            [false, ['city' => null] + $baseData, ['This value should not be blank']],
            [false, ['address' => null] + $baseData, ['This value should not be blank']],
        ];
    }
}
