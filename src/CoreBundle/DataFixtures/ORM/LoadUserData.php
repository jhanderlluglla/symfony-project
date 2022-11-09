<?php

namespace CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Doctrine\Common\Persistence\ObjectManager;

use CoreBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface
{
    /**
     * The dependency injection container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $env = $this->container->getParameter('kernel.environment');
        if ($env == "dev") {
            $user = $this->isUserNotExists('admin@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_SUPER_ADMIN);
                $user->setEmail('admin@gmail.com');
                $user->setEmailCanonical('admin@gmail.com');
                $user->setUsername('admin');
                $user->setUsernameCanonical('admin');
                $user->setEnabled(1);
                $user->setFullName('Admin Admin');
                $user->setPlainPassword('123');
                $user->setZip('75016');

                $manager->persist($user);
            }
            $this->setReference('user-admin', $user);

            $user = $this->isUserNotExists('webmaster-seller-1@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WEBMASTER);
                $user->setEmail('webmaster-seller-1@gmail.com');
                $user->setEmailCanonical('webmaster-seller-1@gmail.com');
                $user->setUsername('webmaster-seller-1');
                $user->setUsernameCanonical('webmaster-seller-1');
                $user->setEnabled(1);
                $user->setFullName('Webmaster seller 1');
                $user->setPlainPassword('123');
                $user->setZip('67290');

                $manager->persist($user);
            }
            $this->setReference('user-seller-1', $user);

            $user = $this->isUserNotExists('webmaster-seller-2@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WEBMASTER);
                $user->setEmail('webmaster-seller-2@gmail.com');
                $user->setEmailCanonical('webmaster-seller-2@gmail.com');
                $user->setUsername('webmaster-seller-2');
                $user->setUsernameCanonical('webmaster-seller-2');
                $user->setEnabled(1);
                $user->setFullName('Webmaster seller 2');
                $user->setPlainPassword('123');
                $user->setZip('1/7389');

                $manager->persist($user);
            }
            $this->setReference('user-seller-2', $user);

            $user = $this->isUserNotExists('webmaster-seller-3@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WEBMASTER);
                $user->setEmail('webmaster-seller-3@gmail.com');
                $user->setEmailCanonical('webmaster-seller-3@gmail.com');
                $user->setUsername('webmaster-seller-3');
                $user->setUsernameCanonical('webmaster-seller-3');
                $user->setEnabled(1);
                $user->setFullName('Webmaster seller 3');
                $user->setPlainPassword('123');
                $user->setZip('29686');

                $manager->persist($user);
            }
            $this->setReference('user-seller-3', $user);

            $user = $this->isUserNotExists('webmaster-buyer-1@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WEBMASTER);
                $user->setEmail('webmaster-buyer-1@gmail.com');
                $user->setEmailCanonical('webmaster-buyer-1@gmail.com');
                $user->setUsername('webmaster-buyer-1');
                $user->setUsernameCanonical('webmaster-buyer-1');
                $user->setEnabled(1);
                $user->setFullName('Webmaster buyer 1');
                $user->setPlainPassword('123');
                $user->setZip('1/7389');

                $manager->persist($user);
            }
            $this->setReference('user-buyer-1', $user);

            $user = $this->isUserNotExists('webmaster-buyer-2@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WEBMASTER);
                $user->setEmail('webmaster-buyer-2@gmail.com');
                $user->setEmailCanonical('webmaster-buyer-2@gmail.com');
                $user->setUsername('webmaster-buyer-2');
                $user->setUsernameCanonical('webmaster-buyer-2');
                $user->setEnabled(1);
                $user->setFullName('Webmaster buyer 2');
                $user->setPlainPassword('123');
                $user->setZip('29686');

                $manager->persist($user);
            }
            $this->setReference('user-buyer-2', $user);

            $user = $this->isUserNotExists('webmaster-buyer-3@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WEBMASTER);
                $user->setEmail('webmaster-buyer-3@gmail.com');
                $user->setEmailCanonical('webmaster-buyer-3@gmail.com');
                $user->setUsername('webmaster-buyer-3');
                $user->setUsernameCanonical('webmaster-buyer-3');
                $user->setEnabled(1);
                $user->setFullName('Webmaster buyer 3');
                $user->setPlainPassword('123');
                $user->setZip('29697');

                $manager->persist($user);
            }
            $this->setReference('user-buyer-3', $user);

            $user = $this->isUserNotExists('writer-1@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WRITER);
                $user->setEmail('writer-1@gmail.com');
                $user->setEmailCanonical('writer-1@gmail.com');
                $user->setUsername('writer-1');
                $user->setUsernameCanonical('writer-1');
                $user->setEnabled(1);
                $user->setFullName('Writer 1');
                $user->setPlainPassword('123');
                $user->setZip('28717');

                $manager->persist($user);
            }
            $this->setReference('user-writer-1', $user);

            $user = $this->isUserNotExists('writer-2@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WRITER);
                $user->setEmail('writer-2@gmail.com');
                $user->setEmailCanonical('writer-2@gmail.com');
                $user->setUsername('writer-2');
                $user->setUsernameCanonical('writer-2');
                $user->setEnabled(1);
                $user->setFullName('Writer 2');
                $user->setPlainPassword('123');
                $user->setZip('30516');

                $manager->persist($user);
            }
            $this->setReference('user-writer-2', $user);

            $user = $this->isUserNotExists('writer-3@gmail.com', $manager);
            if (is_null($user)) {
                $user = new User();
                $user->addRole(User::ROLE_WRITER);
                $user->setEmail('writer-3@gmail.com');
                $user->setEmailCanonical('writer-3@gmail.com');
                $user->setUsername('writer-3');
                $user->setUsernameCanonical('writer-3');
                $user->setEnabled(1);
                $user->setFullName('Writer 3');
                $user->setPlainPassword('123');
                $user->setZip('29696');

                $manager->persist($user);
            }
            $this->setReference('user-writer-3', $user);

            $manager->flush();
        }
    }

    /**
     * @param $emailCanonical
     *
     * @param ObjectManager $manager
     *
     * @return User
     */
    protected function isUserNotExists($emailCanonical, ObjectManager $manager)
    {
        return $manager->getRepository(User::class)->findOneBy(['emailCanonical' => $emailCanonical]);
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}