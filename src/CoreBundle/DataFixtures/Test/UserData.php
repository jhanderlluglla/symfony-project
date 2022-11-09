<?php

namespace CoreBundle\DataFixtures\Test;

use CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class UserData extends AbstractFixture implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->addRole(User::ROLE_WEBMASTER);
        $user->setEmail('webmaster-1@test.com');
        $user->setEmailCanonical('webmaster-1@test.com');
        $user->setUsername('Webmaster');
        $user->setUsernameCanonical('Webmaster-1');
        $user->setEnabled(1);
        $user->setFullName('Webmaster-1 Webmaster');
        $user->setPlainPassword('123');
        $user->setZip('75016');
        $user->setBalance(10000);

        $this->addReference('user-test-webmaster-1', $user);
        $manager->persist($user);


        $user = new User();
        $user->addRole(User::ROLE_WRITER);
        $user->setEmail('writer-1@test.com');
        $user->setEmailCanonical('writer-1@test.com');
        $user->setUsername('Writer-1');
        $user->setUsernameCanonical('Writer-1');
        $user->setEnabled(1);
        $user->setFullName('Writer-1 Writer');
        $user->setPlainPassword('123');
        $user->setZip('75016');
        $user->setBalance(5000);

        $this->addReference('user-test-writer-1', $user);
        $manager->persist($user);

        $user = new User();
        $user->addRole(User::ROLE_SUPER_ADMIN);
        $user->setEmail('admin-1@test.com');
        $user->setEmailCanonical('admin-1@test.com');
        $user->setUsername('Admin-1');
        $user->setUsernameCanonical('Admin-1');
        $user->setEnabled(1);
        $user->setFullName('Admin-1 Admin');
        $user->setPlainPassword('123');
        $user->setZip('75016');
        $user->setBalance(5000);

        $this->addReference('user-test-admin-1', $user);
        $manager->persist($user);

        $user = new User();
        $user->addRole(User::ROLE_WEBMASTER);
        $user->setEmail('webmaster-2@test.com');
        $user->setEmailCanonical('webmaster-2@test.com');
        $user->setUsername('Webmaster-2');
        $user->setUsernameCanonical('Webmaster-2');
        $user->setEnabled(1);
        $user->setFullName('Webmaster-2 Webmaster');
        $user->setPlainPassword('123');
        $user->setZip('75016');
        $user->setBalance(10000);

        $this->addReference('user-test-webmaster-2', $user);
        $manager->persist($user);

        $manager->flush();
    }
}
