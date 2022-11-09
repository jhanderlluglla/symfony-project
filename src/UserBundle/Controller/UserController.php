<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\UserSetting;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Repository\DirectoryBacklinksRepository;
use CoreBundle\Repository\JobRepository;
use CoreBundle\Repository\SettingsRepository;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityNotFoundException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\UserBundle\Form\Factory\FactoryInterface;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserBundle\Form\User\EmailNotificationType;
use UserBundle\Form\User\ModifyBalanceType;
use UserBundle\Form\User\NewUserType;
use UserBundle\Form\User\ProfileFormType;
use UserBundle\Form\User\UserPermissionType;
use UserBundle\Form\User\WebmasterType;
use UserBundle\Form\User\WriterType;
use UserBundle\Form\MessageType;

use CoreBundle\Entity\Settings;
use CoreBundle\Entity\Comission;
use CoreBundle\Entity\Message;
use CoreBundle\Entity\User;
use UserBundle\Security\UserVoter;
use UserBundle\Services\BonusCalculator\CopywritingWriterBonusCalculator;
use UserBundle\Services\BonusCalculator\NetlinkingWriterBonusCalculator;
use UserBundle\Services\WriterService;

/**
 * Class UserController
 *
 * @package UserBundle\Controller
 */
class UserController extends AbstractCRUDController
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $filters = $request->query->all();
        $queryBuilder = $this->getCollectionData($request, $filters);


        /** @var User $user */
        $user = $this->getUser();

        switch ($request->attributes->get('role')) {
            case 'webmaster':
                $voterAction = UserVoter::ACTION_SHOW_WEBMASTER;
                break;
            case 'seo':
                $voterAction = UserVoter::ACTION_SHOW_SEO;
                break;
            default:
                $voterAction = UserVoter::ACTION_SHOW_ALL;
        }

        $this->checkAccess($voterAction, null);

        if ($user->isWriterAdmin()) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notLike('u.roles', $queryBuilder->expr()->literal('%' .User::ROLE_WRITER_ADMIN. '%'))
            );
        }

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 20);

        $adapter = PagerfantaAdapterFactory::getAdapterInstance($queryBuilder);

        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        $arUsers = $pagerfanta->getCurrentPageResults();
        /** @var DirectoryBacklinks $directoryBacklinkRepository */
        $directoryBacklinkRepository = $this->getDoctrine()->getRepository(DirectoryBacklinks::class);
        $statistics = $directoryBacklinkRepository->getStatisticsByUsers($arUsers);

        $userProjectService = $this->get('user.user_projects');
        $projects = $userProjectService->getCountProjects($arUsers);


        return $this->render($this->prepareIndexTemplate(), [
            'collection' => $pagerfanta,
            'statistics' => $statistics,
            'projects' => $projects,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function profileAction(Request $request)
    {
        return $this->render('user/profile.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function balanceAction(Request $request)
    {
        $balance = $this->getUser()->getBalance();

        return $this->json(['balance' => $balance]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
     */
    public function settingsAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $options = [
            'method' => Request::METHOD_PATCH,
            'fieldOptions' => []
        ];

        foreach (User::getNotifications() as $name => $notification) {
            if (isset($notification['roles'])) {
                if (!$user || count(array_intersect($notification['roles'], $user->getRoles())) === 0) {
                    continue;
                }
            }

            $field = [
                'name' => $name,
                'label' => 'profile.settings.' . $name,
                'attr' => [],
                'required' => false
            ];

            if ($user->isNotificationEnabled($name)) {
                $field['attr']['checked'] = true;
            }

            $options['fieldOptions'][] = $field;
        }

        $form = $this->createForm(EmailNotificationType::class, $user, $options);

        $userSettingService = $this->get('core.service.user_setting');

        $settingProposalNotificationFrequency = $userSettingService->getValue(UserSetting::NOTIFICATION_PROPOSAL_FREQUENCY);

        $form->add(User::NOTIFICATION_NEW_PROPOSAL_REMINDER . '_frequency', IntegerType::class, [
            'mapped' => false,
            'required' => false,
            'label' =>  'profile.settings.' . User::NOTIFICATION_NEW_PROPOSAL_REMINDER . '_frequency',
            'data' => $settingProposalNotificationFrequency
        ]);

        if ($request->isMethod(Request::METHOD_PATCH)) {
            $em = $this->getDoctrine()->getManager();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $request->request->get('user_profile_email_notification');

                foreach (User::getNotifications() as $name => $value) {
                    $user->setNotificationEnabled($name, !empty($data[$name]) ? User::NOTIFICATION_ON : User::NOTIFICATION_OFF);
                }

                $userSettingService->setValue(UserSetting::NOTIFICATION_PROPOSAL_FREQUENCY, $data[User::NOTIFICATION_NEW_PROPOSAL_REMINDER . '_frequency']);

                $em->flush();

                return $this->redirectToRoute('user_email_notification');
            }
        }

        return $this->render('user/settings.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function addAction(Request $request)
    {
        $entity = $this->getEntityObject();

        $options = [
            'method' => Request::METHOD_POST,
        ];

        $form = $this->getForm($entity, $options);

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->checkAccess('add', $entity);
                $this->beforeInsert($request, $entity);
                $this->processSubmit($request, $entity, $form);
                $this->afterInsert($request, $entity);

                return $this->getRedirectToRoute($entity, 'add');
            }
        }

        return $this->render($this->prepareAddTemplate(), [
            'form' => $form->createView(),
            'entity' => $entity,
            'additionalData' => $this->getAdditionalData($request),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function comissionAction(Request $request)
    {
        $user = $this->getUser();

        $comissions = [];

        for ($i = 12; $i > -1; $i--) {
            $now = new \DateTime();
            $date = $now->sub(new \DateInterval('P' .$i. 'M'));

            $statistic = $this->getDoctrine()->getRepository(Comission::class)->getStatisticByUser($user, date('Y-m', $date->getTimestamp()));

            $comissions[] = [
                'date' => date('F Y', $date->getTimestamp()),
                'month' => date('m', $date->getTimestamp()),
                'year' => date('Y', $date->getTimestamp()),
                'registered' => $statistic['registered'],
                'earnings' => $statistic['earnings'],
            ];
        }

        return $this->render('user/comission.html.twig',
            [
                'comissions' => $comissions,
            ]);
    }

    /**
     * @param Request $request
     * @param string  $month
     * @param string  $year
     *
     * @return Response
     */
    public function comissionDetailAction(Request $request, $month, $year)
    {
        $user = $this->getUser();

        $comissions = $this->getDoctrine()->getRepository(Comission::class)->getComissionDetail($user, $month, $year);

        return $this->render('user/comission-detail.html.twig',
            [
                'comissions' => $comissions,
                'month' => $month,
                'year' => $year,
            ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function modalAction(Request $request)
    {
        $id = $request->get('id');
        $type = $request->get('type');
        $translator = $this->get('translator');

        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        if (is_null($user)) {
            return $this->json([
                'title' => $translator->trans('modal.error.header', [], 'user'),
                'body' => $translator->trans('modal.error.user_not_found', [], 'user'),
                'result' => 'fail',
            ]);
        }
        $oldBalance = $user->getBalance();

        $message = null;

        switch ($type) {
            case 'send_message':
                $messageService = $this->get('user.message');
                $options = [
                    'recipient' => $messageService->getFormRecipientsList(),
                ];

                $message = new Message();

                $form = $this->createForm(MessageType::class, $message, $options);
                $form->get('recipient')->setData($id);
                break;

            case 'edit_password':
                $this->checkAccess(UserVoter::ACTION_CHANGE_PASSWORD, $user);
                /** @var $formFactory FactoryInterface */
                $formFactory = $this->container->get('fos_user.change_password.form.factory');

                /** @var Form $form */
                $form = $formFactory->createForm([
                    'validation_groups' => ['ResetPassword']
                ]);
                $form->remove('current_password');
                $form->setData($user);
                break;

            case 'change_permission':
                if (!$this->getUser()->isSuperAdmin()) {
                    throw $this->createAccessDeniedException();
                }
                $options = [
                    'method' => Request::METHOD_PATCH,
                ];

                if ($request->isMethod(Request::METHOD_POST) || $request->isMethod(Request::METHOD_PATCH)) {
                    $data = null;
                } else {
                    $data = $this->get('core.service.access_manager')->getPermissionList($user);
                }
                $form = $this->createForm(UserPermissionType::class, $data, $options);
                break;

            case 'modify_balance':
                $this->checkAccess(UserVoter::ACTION_MODIFY_BALANCE, $user);
                $options = [
                    'method' => Request::METHOD_PATCH,
                ];

                $form = $this->createForm(ModifyBalanceType::class, $user, $options);
                break;
        }

        if ($request->isMethod(Request::METHOD_POST) || $request->isMethod(Request::METHOD_PATCH)) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $response = [
                    'result' => 'success',
                    'id' => $id,
                    'type' => $type,
                ];
                $em = $this->getDoctrine()->getManager();
                switch ($type) {
                    case 'send_message':
                        $message = $form->getData();
                        $data = $request->request->get('message');
                        if ($message instanceof Message) {
                            $messageService = $this->get('user.message');
                            $message
                                ->setSendUser($this->getUser())
                                ->setReceiveUser($user)
                            ;

                            $messageService->sendMessage($message, isset($data['sendMessage']));

                            $em->persist($message);
                            $em->flush();

                            $response['message'] = $translator->trans('ajax.send.success', [], 'message');
                        }
                        break;

                    case 'edit_password':
                        $userManager = $this->container->get('fos_user.user_manager');
                        $userManager->updatePassword($user);
                        $em->flush();
                        break;
                    case 'modify_balance':
                        $solder = $response['message'] = $user->getBalance();
                        $user->setBalance($oldBalance);
                        $transactionService = $this->get('core.service.transaction');
                        $credit = 0;
                        $debit = 0;
                        if ($oldBalance > $solder) {
                            $credit = $oldBalance - $solder;
                            $idTranslate = 'account.modify_balance_remove';
                        } else {
                            $debit = $solder - $oldBalance;
                            $idTranslate = 'account.modify_balance_add';
                        }
                        $transactionService->handling(
                            $user,
                            new TransactionDescriptionModel($idTranslate),
                            $debit,
                            $credit,
                            null,
                            [User::TRANSACTION_TAG_MODIFY_BALANCE]
                        );
                        break;

                    case 'change_permission':
                        $data = $form->getData();
                        $this->changePermission($data, $user);
                        break;
                }

                return $this->json($response);
            }
        }

        $body = $this->renderView('user/' . $type . '.html.twig', [
            'form' => $form->createView(),
            'id' => $id,
            'type' => $type,
        ]);

        return $this->json([
            'title' => $translator->trans(implode('.', ['modal', $type, 'title']), [], 'user'),
            'body' => $body,
            'result' => 'fail',
        ]);
    }

    /**
     * @param array $data
     * @param User $user
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function changePermission($data, User $user)
    {
        $accessControllerService = $this->get('core.service.access_manager');
        foreach (UserSetting::getPermissions() as $permission) {
            if (isset($data[$permission]) && ($data[$permission] === true || $data[$permission] === '1')) {
                $value = true;
            } else {
                $value = false;
            }

            $accessControllerService->setAccess($permission, $value, $user);
        }
    }

    /**
     * @param User  $entity
     * @param array $options
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        $translator = $this->get('translator');

        if ($entity->hasRole(User::ROLE_SUPER_ADMIN)) {
            $form = ProfileFormType::class;
        } elseif ($entity->hasRole(User::ROLE_WEBMASTER)) {
            $spending = $entity->getSpending();

            if ($spending <= 0) {
                $spending = $this->getDoctrine()->getRepository(Settings::class)->getSettingValue(Settings::TARIFF_WEB);
            }

            $affiliationTariff = $this->getDoctrine()->getRepository(Settings::class)->getSettingValue('affiliation');

            $options = [
                    'spending_helper' => $translator->trans('form.spending_helper', ['%spending%' => $spending], 'user'),
                    'affiliation_tariff_helper' => $translator->trans('form.affiliation_tariff_helper', ['%affiliation_tariff%' => $affiliationTariff], 'user'),
                ] + $options;

            $form = WebmasterType::class;
        } elseif ($entity->hasRole(User::ROLE_WRITER) ||
            $entity->hasRole(User::ROLE_WRITER_NETLINKING) ||
            $entity->hasRole(User::ROLE_WRITER_COPYWRITING) ||
            $entity->hasRole(User::ROLE_WRITER_ADMIN)
        ) {
            $spending = $entity->getSpending();

            if ($spending <= 0) {
                $spending = $this->getDoctrine()->getRepository(Settings::class)->getSettingValue('remuneration');
            }

            $options = [
                    'spending_helper' => $translator->trans('form.spending_helper', ['%spending%' => $spending], 'user'),
                ] + $options;

            $form = WriterType::class;
        } else {
            $form = NewUserType::class;
        }

        $options['role_choices'] = $this->getAvailableRoleForForm();

        $form = $this->createForm($form, $entity, $options);

        $accessManager = $this->get('core.service.access_manager');
        if (!$accessManager->canManageEarning()) {
            $form->remove('spending');
            $form->remove('copyWriterRate');
            $form->remove('affiliationTariff');
            $form->remove('discountRate');
        }

        if (!$this->getUser()->isSuperAdmin()) {
            $form->remove('permissions');
        } elseif ($form->has('permission')) {
            $this->get('core.helper.form')->formSetValues($form->get('permission'), $this->get('core.service.access_manager')->getPermissionList($entity));
        }

        return $form;
    }


    private function getAvailableRoleForForm()
    {
        $roles = [];
        if ($this->getUser()->isSuperAdmin()) {
            $roles['form.role_superadmin'] = User::ROLE_SUPER_ADMIN;
            $roles['form.role_writer_admin'] = User::ROLE_WRITER_ADMIN;
        }

        $accessManager = $this->get('core.service.access_manager');
        if ($accessManager->canManageWriterUser()) {
            $roles['form.role_writer_netlinking'] = User::ROLE_WRITER_NETLINKING;
            $roles['form.role_writer_copywriting'] = User::ROLE_WRITER_COPYWRITING;
            $roles['form.role_writer'] = User::ROLE_WRITER;
        }

        if ($accessManager->canManageWebmasterUser()) {
            $roles['form.role_webmaster'] = User::ROLE_WEBMASTER;
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return User::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new User();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        if ($entity->hasRole(User::ROLE_SUPER_ADMIN)) {
            $parameters = [
                'role' => 'administrator'
            ];
        } else if ($entity->hasRole(User::ROLE_WEBMASTER)) {
            $parameters = [
                'role' => 'webmaster'
            ];
        } else if ($entity->hasRole(User::ROLE_WRITER) ||
                   $entity->hasRole(User::ROLE_WRITER_NETLINKING) ||
                   $entity->hasRole(User::ROLE_WRITER_COPYWRITING) ||
                   $entity->hasRole(User::ROLE_WRITER_ADMIN)
        ) {
            $parameters = [
                'role' => 'seo'
            ];
        }

        return $this->redirectToRoute('user_list', $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        switch ($request->get('role')) {
            case 'seo':
                $roles = [User::ROLE_WRITER, User::ROLE_WRITER_NETLINKING, User::ROLE_WRITER_COPYWRITING];
                break;
            case 'webmaster':
                $roles = [User::ROLE_WEBMASTER];
                break;
            case 'administrator':
                $roles = [User::ROLE_WRITER_ADMIN, User::ROLE_SUPER_ADMIN];
                break;
            default:
                $roles = [];
        }

        $filters = $filters + [
                'roles' => $roles,
            ];

        return $this->getDoctrine()->getRepository($this->getEntity())->filter($filters);
    }

    /**
     * @param Request $request
     * @param User    $entity
     */
    protected function beforeInsert(Request $request, $entity)
    {
        if ($this->getUser() && $this->getUser()->hasRole(User::ROLE_SUPER_ADMIN)) {
            $entity->setEnabled(true);
        }
    }

    /**
     * @param Request $request
     * @param User $entity
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function afterInsert(Request $request, $entity)
    {
        $data = $request->get('new_user');
        if (isset($data['permission'])) {
            $this->changePermission($data['permission'], $entity);
        }
    }

    /**
     * @param Request $request
     * @param User $oldEntity
     * @param User $entity
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function afterUpdate(Request $request, $oldEntity, $entity)
    {
        $data = $request->get('edit_writer') ?: $request->get('edit_webmaster');
        if (isset($data['permission'])) {
            $this->changePermission($data['permission'], $entity);
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function earningAction(Request $request)
    {
        $user = $this->getUser();
        $currentMonth = $month = date('m');
        $lastMonth = $month = date('m', strtotime("last month"));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        /** @var JobRepository $jobRepository */
        $jobRepository = $this->getDoctrine()->getRepository(Job::class);

        /** @var DirectoryBacklinksRepository $backlinksRepository */
        $backlinksRepository = $this->getDoctrine()->getRepository(DirectoryBacklinks::class);

        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->getDoctrine()->getRepository(Settings::class);

        $copywritingEarningsCurrentMonth = $userRepository->getCopywriterEarningsForMonth($user, $currentMonth);
        $copywritingEarningsLastMonth = $userRepository->getCopywriterEarningsForMonth($user, $lastMonth);

        $directoryEarningsCurrentMonth = $jobRepository->getCopywriterEarningsByMonth($user, new \DateTime());
        $lastMonth = new \DateTime();
        $directoryEarningsLastMonth = $jobRepository->getCopywriterEarningsByMonth($user, $lastMonth->modify('-1 month'));

        $copywritingLikes = $copywritingDislikes = 0;
        if ($resultArray = $userRepository->getLikes(null, $user)) {
            $copywritingLikes = $resultArray[0]['likes'];
            $copywritingDislikes = $resultArray[0]['dislikes'];
        }

        $directoryLikes = $jobRepository->getLikes($user);

        $backlinksFound = $backlinksRepository->getCount($user, DirectoryBacklinks::STATUS_FOUND);
        $backlinksNotFound = $backlinksRepository->getCount($user, DirectoryBacklinks::STATUS_NOT_FOUND);

        /** @var CopywritingWriterBonusCalculator $writerBonusCalculator */
        $writerBonusCalculator = $this->get('user.copywriting.writer_bonus_calculator');

        /** @var NetlinkingWriterBonusCalculator $netlinkingWriterBonusCalculator */
        $netlinkingWriterBonusCalculator = $this->get('user.netlinking.writer_bonus_calculator');

        $bonuses = [
            'earningBonus' => $writerBonusCalculator->countBonusForEarning($user),
            'copywritingRating' => $writerBonusCalculator->countBonusForRating($user),
            'netlinkingLikes' => $netlinkingWriterBonusCalculator->getBonusByLikes($user),
            'netlinkingBacklinks' => $netlinkingWriterBonusCalculator->getBonusByBacklinks($user),
        ];

        /** @var WriterService $writerService */
        $writerService = $this->get('user.writer');

        $pricePer100Words = $user->getCopyWriterRate() ? $user->getCopyWriterRate() : $settingsRepository->getSettingValue(Settings::WRITER_PRICE_PER_100_WORDS);
        $yourEarning = [
            'copywriting' => $pricePer100Words + $bonuses['earningBonus'] + $bonuses['copywritingRating'],
            'directory' => $netlinkingWriterBonusCalculator->calculate($user, $writerService->getCompensation($user)),
        ];
        return $this->render('earning/index.html.twig',[
            'copywriting' =>[
                'lastMonth' => round($copywritingEarningsLastMonth, 2) ?: 0,
                'currentMonth' => round($copywritingEarningsCurrentMonth, 2) ?: 0,
                'likes' => $copywritingLikes,
                'dislikes' => $copywritingDislikes,
            ],
            'directory' =>[
                'lastMonth' => round($directoryEarningsLastMonth ,2),
                'currentMonth' => round($directoryEarningsCurrentMonth, 2),
                'likes' => $directoryLikes['likes'],
                'dislikes' => $directoryLikes['dislikes'],
            ],
            'backLinksFound' => $backlinksFound,
            'backlinksNotFound' => $backlinksNotFound,
            'bonuses' => $bonuses,
            'yourEarning' => $yourEarning,
        ]);
    }

    /**
     * @return Response
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function paymentAction()
    {

        $userService = $this->get('core.service.user');


        $buffer = new BufferedOutput();
        $table = new Table($buffer);

        $table->setHeaders(array('User', 'Amount'));

        $rows = [];
        $totalAmount = 0;

        $payments = $userService->paymentAll(['role' => 'seo']);
        foreach ($payments as $payment) {
            $rows[] = [$payment['user'], number_format($payment['amount'], 2, '.', ' ')];
            $totalAmount += $payment['amount'];
        }

        $rows[] = new TableSeparator();
        $rows[] = ['', number_format($totalAmount, 2, '.', ' ')];

        $table->setRows($rows);

        $table->render();

        $response = new Response();
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Content-Disposition', 'attachment; filename="payment-' . date('Y-m-d') . '.txt"');
        $response->setContent($buffer->fetch());

        return $response;
    }

    /**
     * @return null|string
     */
    protected function getVoterNamespace()
    {
        return User::class;
    }
}
