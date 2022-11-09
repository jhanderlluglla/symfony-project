<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Affiliation;
use CoreBundle\Entity\AffiliationClick;
use CoreBundle\Entity\Anchor;
use CoreBundle\Entity\Comission;
use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingArticleRating;
use CoreBundle\Entity\CopywritingImage;
use CoreBundle\Entity\CopywritingKeyword;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Invoice;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\Message;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\NetlinkingProjectComments;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Entity\Transaction;
use CoreBundle\Services\BwaInfo;
use CoreBundle\Services\GenerateInvoiceService;
use CoreBundle\Services\MajesticInfo;
use CoreBundle\Utils\ExchangeSiteUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Doctrine\ORM\EntityManager;

use CoreBundle\Entity\Category;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ImportCommand extends ContainerAwareCommand
{

    const COUNT = 30;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var EntityManager
     */
    private $defaultEntityManager;

    /**
     * @var EntityManager
     */
    private $releaseEntityManager;

    protected function configure()
    {
        $this->setName('app:import')
            ->setDescription('Update table of date activity')
            ->addArgument('data', InputArgument::OPTIONAL, 'Data to update (1.date)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $this->output = $output;

            $datas = [
                'all',
                'category',
                'update_users_passwords',
                'longKeywords',
                'update_copywriting_articles',
                'create_copywriting_articles_for_progress_orders',
                'update_start_at_of_schedule_tasks',
                'settings',
                'directory',
                'invoice',
                'transaction',
                'copywriting',
                'netlinking',
                'message',
                'jobs',
                'affiliation',
                'update-directory-list',
            ];

            $helper = $this->getHelper('question');

            $question = new ChoiceQuestion(
                'Please select the section',
                $datas,
                0
            );
            $question->setErrorMessage('Color %s is invalid.');

            $data = $helper->ask($input, $output, $question);

            $this->defaultEntityManager = $this->getContainer()->get("doctrine")->getManager('default');
            $this->releaseEntityManager = $this->getContainer()->get("doctrine")->getManager('release');

            switch ($data) {
                case 'all':
                    break;

                case 'settings':
                    $this->importSettings();
                    break;

                case 'category':
                    $category = $this->defaultEntityManager->getRepository(Category::class)->findOneBy(['name' => 'root']);
                    if ($category === null) {
                        $category = new Category();
                        $category
                            ->setName('root')
                            ->setLanguage('fr')
                        ;
                    }

                    $this->defaultEntityManager->persist($category);
                    $this->defaultEntityManager->flush();

                    $this->importCategory(0, $category);
                    break;
                case 'longKeywords':
                    $this->saveLongCopywritingKeywords();
                    break;
                case 'update_copywriting_articles':
                    $this->updateCopywritingArticles();
                    break;
                case 'create_copywriting_articles_for_progress_orders':
                    $this->createCopywrtingArticlesForOrderInProgress();
                    break;
                case 'update_start_at_of_schedule_tasks':
                    $this->updateStartAtOfScheduleTasks();
                    break;
                case 'update_users_passwords':
                    $this->updateUsersPasswords();
                    break;

                case 'directory':
                    $this->importDirectory();
                    break;
                case 'invoice':
                    $this->importInvoice();
                    break;
                case 'transaction':
                    $this->importTransaction();
                    break;
                case 'copywriting':
                    $this->importCopywriting();
                    $this->importCopywritingLikes();
                    break;
                case 'netlinking':
                    $this->importNetlinkingProject();
//                    $this->importAnchor();
//                    $this->importNetlinkingComments();
                    break;
                case 'message':
                    $this->importMessage();
                    break;
                case 'jobs':
//                    $this->importJobs();
                    $this->updateJobs();
//                    $this->importRejectedJobs();
                    break;
                case 'affiliation':
                    $this->importAffiliation();
                    $this->importAffiliationClick();
                    break;
                case 'update-directory-list':
                    $this->updateDirectoryList();
                    break;
            }

            $output->writeln('Done');
        }catch (\Exception $e){
            $this->output->writeln("ERROR: {$e->getMessage()}|LINE: {$e->getLine()}");
            $this->output->writeln($e->getTraceAsString());
        }
    }

    /**
     * @param int      $parentId
     * @param Category $parent
     */
    private function importCategory($parentId, $parent)
    {
        $sql = 'SELECT id, value, parent FROM categories_v2 WHERE parent = :parent';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute([
            'parent' => $parentId
        ]);

        $result = $stmt->fetchAll();

        foreach ($result as $data) {
            $category = $this->defaultEntityManager->getRepository(Category::class)->findOneBy([
                "name" => $data['value']
            ]);
            if ($category === null) {
                $category = new Category();
                $category
                    ->setName($data['value'])
                    ->setLanguage('fr')
                    ->setParent($parent)
                    ->setExternalId($data['id'])
                ;

                $this->defaultEntityManager->persist($category);
            } else {
                $category->setExternalId($data['id']);
            }
            $this->defaultEntityManager->flush();

            $this->importCategory($data['id'], $category);
        }
    }

    private function importSettings()
    {
        $sql = 'SELECT libelle, name, description, valeur FROM parametres';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll();

        foreach ($result as $data) {
            $setting = $this->defaultEntityManager->getRepository(Settings::class)->findOneBy(['identificator' => $data['name']]);
            if($setting !== null) continue;

            $settings = new Settings();
            $settings
                ->setName($data['libelle'])
                ->setIdentificator($data['name'])
                ->setValue($data['valeur'])
            ;

            $this->defaultEntityManager->persist($settings);

        }

        $this->defaultEntityManager->flush();
    }

    private function updateStartAtOfScheduleTasks()
    {
        $limit = 1000;
        $offset = 0;

        $netlinkingRepository = $this->defaultEntityManager->getRepository(NetlinkingProject::class);
        $qb = $netlinkingRepository->createQueryBuilder('np');
        $qb
            ->innerJoin("np.scheduleTasks", 'st')
            ->andWhere(
                'st.startAt = \'2222-02-22 22:22:22\''
            )
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;
        $netlinkingProjects = $qb->getQuery()->getResult();
        while ($netlinkingProjects) {
            /** @var NetlinkingProject $netlinkingProject */
            foreach ($netlinkingProjects as $netlinkingProject) {
                $scheduleTasks = $netlinkingProject->getScheduleTasks();
                $affectedAt = clone $netlinkingProject->getAffectedAt();

                for ($i=0; $i<count($scheduleTasks); $i++) {
                    $scheduleTask = $scheduleTasks[$i];

                    if ($i > 0 && $i % $netlinkingProject->getFrequencyDirectory() === 0) {
                        $affectedAt->modify("+{$netlinkingProject->getFrequencyDay()} day");
                    }
                    $scheduleTask->setStartAt(clone $affectedAt);
                }
                $this->output->writeln($this->formatMessage("netlinkingProject ID: {$netlinkingProject->getId()} UPDATED"));
            }
            $this->output->writeln($this->formatMessage("netlinkingProjects SAVED"));
            $this->defaultEntityManager->flush();
            $offset += $limit;

            $qb->setFirstResult($offset);
            $netlinkingProjects = $qb->getQuery()->getResult();
        }
    }

    private function updateUsersPasswords()
    {
        $limit = 50;
        $offset = 0;
        $userManager = $this->getContainer()->get('fos_user.user_manager');

        $userRepository = $this->defaultEntityManager->getRepository(User::class);
        $qb = $userRepository->createQueryBuilder('u');
        $qb
            ->andWhere(
                $qb->expr()->isNotNull('u.externalId')
            )
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;
        $users = $qb->getQuery()->getResult();
        while ($users) {
            foreach ($users as $user) {
                $user->setPlainPassword("11Yhgl8nWW05");
                $userManager->updatePassword($user);
                $this->output->writeln($this->formatMessage("user ID: {$user->getId()} UPDATED"));
            }
            $this->defaultEntityManager->flush();
            $offset += $limit;

            $qb->setFirstResult($offset);
            $users = $qb->getQuery()->getResult();
        }
    }

    private function importDirectory()
    {
        $sql = 'SELECT * FROM annuaires';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll();

        $directoryRepository = $this->defaultEntityManager->getRepository(Directory::class);
        foreach ($result as $data) {
            $directory = $directoryRepository->findOneBy(['externalId' => $data['id']]);


            if (is_null($directory)) {
                $webmasterPartner = $this->getPartner($data['webmasterPartenaire']);

                $age = null;

                if (!empty($data['age'])) {
                    $age = new \DateTime();
                    $age->setTimestamp($data['age']);
                }

                $directory = new Directory();
                $directory
                    ->setName($data['annuaire'])
                    ->setPageRank($data['page_rank'])
                    ->setActive($data['active'])
                    ->setWebmasterAnchor($data['webmasterAncre'])
                    ->setWebmasterOrder($data['webmasterConsigne'])
                    ->setWebmasterPartner($webmasterPartner)
                    ->setPersonalAccountWebmaster($data['ComptePersoWebmaster'])
                    ->setInstructions($data['consignes'])
                    ->setNddTarget($data['nddcible'])
                    ->setPageCount($data['page_count'])
                    ->setAcceptInnerPages($data['accept_inner_pages'])
                    ->setAcceptLegalInfo($data['accept_legal_info'])
                    ->setAcceptCompanyWebsites($data['accept_company_websites'])
                    ->setLinkSubmission($data['link_submission'])
                    ->setVipState($data['vip_state'])
                    ->setVipText($data['vip_text'])
                    ->setMinWordsCount($data['min_words_count'])
                    ->setMaxWordsCount($data['max_words_count'])
                    ->setMajesticTrustFlow($data['tf'])
                    ->setAlexaRank($data['ar'])
                    ->setAge($age)
                    ->setTotalReferringDomain($data['tdr'])
                    ->setTotalBacklink($data['tb'])
                    ->setValidationTime($data['tv'])
                    ->setValidationRate($data['tav'])
                    ->setExternalId($data['id'])
                ;

                if (!empty($data['tarifW'])) {
                    $directory->setTariffExtraWebmaster($data['tarifW']);
                }
                if (!empty($data['tarifR'])) {
                    $directory->setTariffExtraSeo($data['tarifR']);
                }
                if (!empty($data['WebPartenairePrice'])) {
                    $directory->setTariffWebmasterPartner($data['WebPartenairePrice']);
                }
                $this->defaultEntityManager->persist($directory);
            }
        }

        $this->defaultEntityManager->flush();
    }

    private function importCopywriting($externalId = null){
        if($externalId === null) {

            $sql = 'SELECT * FROM redaction_projects ORDER BY id DESC';
            $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
            $stmt->execute();

            $result = $stmt->fetchAll();

            foreach ($result as $data) {
                $copywritingProject = $this->defaultEntityManager->getRepository(CopywritingProject::class)->findOneBy(['externalId' => $data['id']]);

                if (is_null($copywritingProject)) {
                    $this->importOneCopywriting($data);
                }
            }
        }else{
            $copywritingProject = $this->defaultEntityManager->getRepository(CopywritingProject::class)->findOneBy(['externalId' => $externalId]);
            if(!is_null($copywritingProject)){
                $this->output->writeln($this->formatMessage("copywritingProject EXTERNALID: {$externalId} FOUND"));

                return $copywritingProject;
            }

            if(is_null($copywritingProject)){
                $sql = 'SELECT * FROM redaction_projects WHERE id = :external_id';
                $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
                $params['external_id'] = $externalId;
                $stmt->execute($params);

                $data = $stmt->fetch(\PDO::FETCH_ASSOC);

                return $this->importOneCopywriting($data);
            }
        }
    }

    private function importOneCopywriting($data){
        if(!empty($data)) {
            $webmasterPartner = $this->getPartner($data['webmaster_id']);

            $copywritingProject = new CopywritingProject();
            $copywritingProject->setCustomer($webmasterPartner);
            $copywritingProject->setTitle($data['title']);
            $copywritingProject->setDescription($data['desc']);
            $copywritingProject->setTemplate($data['is_template']);
            $copywritingProject->setCreatedAt(new \DateTime($data['created_time']));
            $copywritingProject->setExternalId($data['id']);

            $this->defaultEntityManager->persist($copywritingProject);
            $this->defaultEntityManager->flush();
            $this->output->writeln($this->formatMessage("copywritingProject EXTERNALID: {$data['id']} SAVED ID: {$copywritingProject->getId()}"));
            $this->createCopywritingOrder($data, $copywritingProject, $webmasterPartner);

            return $copywritingProject;
        }
    }

    /**
     * @param int $externalId
     * @return User|null|object
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function getPartner($externalId)
    {
        $user = $this->defaultEntityManager->getRepository(User::class)->findOneBy(['externalId' => $externalId]);

        if(!is_null($user)){
            $this->output->writeln($this->formatMessage("user EXTERNALID: $externalId FOUND"));
        }
        else {
            $sql = 'SELECT * FROM utilisateurs WHERE id = :externalId';
            $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
            $params['externalId'] = $externalId;
            $stmt->execute($params);

            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
            $roles = [
                1 => User::ROLE_SUPER_ADMIN,
                2 => User::ROLE_SUPER_ADMIN,
                3 => User::ROLE_WRITER,
                4 => User::ROLE_WEBMASTER,
            ];

            if (!empty($userData)) {
                $this->output->writeln($this->formatMessage("user EXTERNALID: $externalId STARTED"));

                $joinTime = null;
                $lastlogin = null;
                $tentatives = null;
                $naissance = null;
                $lastPayment = null;

                if (!empty($userData['joinTime'])) {
                    $joinTime = new \DateTime();
                    $joinTime->setTimestamp($userData['joinTime']);
                }

                if (!empty($userData['lastlogin'])) {
                    $lastlogin = new \DateTime();
                    $lastlogin->setTimestamp($userData['lastlogin']);
                }

                if (!empty($userData['tentatives'])) {
                    $tentatives = new \DateTime();
                    $tentatives->setTimestamp($userData['tentatives']);
                }

                if (!empty($userData['naissance'])) {
                    $naissance = str_replace('/', '.', $userData['naissance']);
                    $naissance = new \DateTime($naissance);
                }

                if (!empty($userData['lastPayment'])) {
                    $lastPayment = new \DateTime();
                    $lastPayment->setTimestamp($userData['lastPayment']);
                }

                $user = new User();
                $creditCost = $this->defaultEntityManager->getRepository(Settings::class)->getSettingValue('prix_achat_credit');
                if($creditCost === null){
                    $creditCost = 10;
                }
                $balance = $userData['solde'] + $userData['credit'] * $creditCost;

                if($userData['typeutilisateur'] == 3){
                    if($userData['annuaire_writer'] && $userData['redaction_writer']){
                        $user->setRoles([User::ROLE_WRITER]);
                    }elseif($userData['redaction_writer']){
                        $user->setRoles([User::ROLE_WRITER_COPYWRITING]);
                    }elseif($userData['annuaire_writer']){
                        $user->setRoles([User::ROLE_WRITER_NETLINKING]);
                    }
                }else{
                    $user->setRoles([$roles[$userData['typeutilisateur']]]);
                }
                $user
                    ->setFullName($userData['nom'] . ' ' . $userData['prenom'])
                    ->setEmailCanonical($userData['email'])
                    ->setEmail($userData['email'])
                    ->setUsernameCanonical($userData['username'])
                    ->setUsername($userData['username'])
                    ->setBalance($balance)
                    ->setLastLogin($lastlogin)
                    ->setAttempts($tentatives)
                    ->setDayOfBirth($naissance)
                    ->setConnected($userData['connected'])
                    ->setAffiliationTariff($userData['styleAdmin'])
                    ->setEnabled($userData['active'])
                    ->setContractAccepted($userData['contractAccepted'])
                    ->setLastPaymentDate($lastPayment)
                    ->setAmountLastPayment($userData['AmountLastPayment'])
                    ->setProjectHiddenEditor($userData['writers_choosing'])
                    ->setCopyWriterRate($userData['tarif_redaction'])
                    ->setBonusProjects($userData['bonus_projects'])
                    ->setContractDraftingAccepted($userData['redaction_contract_accepted'])
                    ->setTrusted($userData['trusted'])
                    ->setPlainPassword('123')
                    ->setExternalId($userData['id']);

                if(!empty($userData['created'])){
                    $user->setCreatedAt(new \DateTime($userData['created']));
                }else{
                    $user->setCreatedAt($joinTime);
                }
                if(!empty($userData['frais'])){
                    $user->setSpending($userData['frais']);
                }
                if(!empty($userData['siteweb'])){
                    $user->setWebSite($userData['siteweb']);
                }
                if(!empty($userData['telephone'])){
                    $user->setPhone($userData['telephone']);
                }
                if(!empty($userData['adresse'])){
                    $user->setAddress($userData['adresse']);
                }
                if(!empty($userData['codepostal'])){
                    $user->setZip($userData['codepostal']);
                }
                if(!empty($userData['ville'])){
                    $user->setCity($userData['ville']);
                }
                if(!empty($userData['numtva'])){
                    $user->setVatNumber($userData['numtva']);
                }
                if (!empty($userData['lastIP'])) {
                    $user->setAffiliation($this->getPartner($userData['lastIP']));
                }

                if (!empty($userData['settings'])) {
                    $settings = unserialize($userData['settings']);
                    foreach (User::getNotifications() as $name => $notification) {
                        if (isset($settings[$name])) {
                            $user->setNotificationEnabled($name, $settings[$name]);
                        }
                    }
                }

                $this->defaultEntityManager->persist($user);
                $this->defaultEntityManager->flush();
                $this->output->writeln($this->formatMessage("user EXTERNALID: {$externalId} SAVED ID: {$user->getId()}"));
            }
        }
        return $user;
    }

    /**
     * @param $data
     * @param CopywritingProject $copywritingProject
     * @param $webmasterPartner
     * @return CopywritingOrder|null|object
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCopywritingOrder($data, $copywritingProject, $webmasterPartner){
        $copywritingOrder = $this->defaultEntityManager->getRepository(CopywritingOrder::class)->findOneBy(['externalId' => $data['id']]);

        if(is_null($copywritingOrder)){
            $this->output->writeln($this->formatMessage("copywritingOrder EXTERNALID: {$data['id']} STARTED"));
            $copywritingOrder = new CopywritingOrder();
            $copywritingOrder->setProject($copywritingProject);
            $copywritingOrder->setCustomer($webmasterPartner);
            $copywritingOrder->setCopywriter($this->getPartner($data['affectedTO']));
            if($data['prop_id'] !== "0"){
                $copywritingOrder->setExchangeProposition($this->getExchangeProposition($data['prop_id']));
            }
            if(!empty($data['article_title'])){
                $copywritingOrder->setTitle($data['article_title']);
            }
            if(!empty($data['instructions'])){
                $copywritingOrder->setInstructions($data['instructions']);
            }
            $links = unserialize($data['links_array']);
            if (!is_array($links)) {
                $copywritingOrder->setLinks([]);
            }else{
                $copywritingOrder->setLinks($links);
            }
            $copywritingOrder->setWordsNumber($data['words_count']);
            $copywritingOrder->setImagesNumber($data['img_count']);
            $copywritingOrder->setMetaTitle($data['meta_title']);
            if($data['meta_desc'] === ""){
                $copywritingOrder->setMetaDescription(null);
            }else {
                $copywritingOrder->setMetaDescription($data['meta_desc']);
            }
            $copywritingOrder->setHeaderOneSet($data['H1_set']);
            $copywritingOrder->setHeaderTwoStart($data['H2_start']);
            $copywritingOrder->setHeaderTwoEnd($data['H2_end']);
            $copywritingOrder->setHeaderThreeStart($data['H3_start']);
            $copywritingOrder->setHeaderThreeEnd($data['H3_end']);
            $copywritingOrder->setBoldText($data['bold_text']);
            $copywritingOrder->setUlTag($data['UL_set']);
            $copywritingOrder->setKeywordsPerArticleFrom($data['seo_percent_start']);
            $copywritingOrder->setKeywordsPerArticleTo($data['seo_percent_end']);
            $copywritingOrder->setKeywordInMetaTitle($data['seo_meta_title']);
            $copywritingOrder->setKeywordInHeaderOne($data['seo_H1_set']);
            $copywritingOrder->setKeywordInHeaderTwo($data['seo_H2_set']);
            $copywritingOrder->setKeywordInHeaderThree($data['seo_H3_set']);
            $copywritingOrder->setAmount($data['amount']);
            $copywritingOrder->setImagesPerArticleFrom($data['images_count_from']);
            $copywritingOrder->setImagesPerArticleTo($data['images_count_to']);

            switch ($data['status']){
                case 'finished':
                    $copywritingOrder->setStatus(CopywritingOrder::STATUS_COMPLETED);
                    break;
                case 'waiting':
                    $copywritingOrder->setStatus(CopywritingOrder::STATUS_WAITING);
                    break;
                case 'progress':
                    $copywritingOrder->setStatus(CopywritingOrder::STATUS_PROGRESS);
                    break;
                case 'modification':
                    $copywritingOrder->setStatus(CopywritingOrder::STATUS_DECLINED);
                    break;
                case 'review':
                    $copywritingOrder->setStatus(CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN);
                    break;
            }
            $copywritingOrder->setViewed($data['viewed']);
            $copywritingOrder->setOptimized($data['optimized']);
            $copywritingOrder->setCreatedAt(new \DateTime($data['created_time']));
            $copywritingOrder->setTakenAt(new \DateTime($data['affected_time']));
            $copywritingOrder->setApprovedAt(new \DateTime($data['affected_time']));
            $copywritingOrder->setExternalId($data['id']);

            $this->saveCopywritingImages($copywritingOrder, $data['id']);
            $keywords = $this->saveCopywritingKeywords($copywritingOrder, $data['id']);

            $this->defaultEntityManager->persist($copywritingOrder);
            $this->defaultEntityManager->flush();
            $this->output->writeln($this->formatMessage("copywritingOrder EXTERNALID: {$data['id']} SAVED ID: {$copywritingOrder->getId()}"));

            $this->createCopywritingArticle($copywritingOrder, $keywords);
        }

        return $copywritingOrder;
    }

    private function getExchangeSite($exchangeId){
        $exchangeSite = $this->defaultEntityManager->getRepository(ExchangeSite::class)->findOneBy(['externalId' => $exchangeId]);

        if(is_null($exchangeSite)){
            $sql = 'SELECT * FROM echange_sites WHERE id = :exchange_id';
            $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
            $params['exchange_id'] = $exchangeId;
            $stmt->execute($params);

            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            if(!empty($data)){
                $this->output->writeln($this->formatMessage("exchangeSite EXTERNALID: {$exchangeId} STARTED"));
                $exchangeSite = new ExchangeSite();
                $exchangeSite->setUser($this->getPartner($data['user_id']));
                $url = trim($data['url'], '/');
                $exchangeSite->setUrl($url);
                $creditCost = $this->defaultEntityManager->getRepository(Settings::class)->getSettingValue('prix_achat_credit');
                if($creditCost === null){
                    $creditCost = 10;
                }
                $exchangeSite->setCredits($data['credit'] * $creditCost);

//                $urlInfo = parse_url($url);
//                $domain = str_ireplace("www.", "", $urlInfo["host"]);
//
//                /** @var MajesticInfo $majesticInfo */
//                $majesticInfo = $this->getContainer()->get('core.service.majestic_info');
//
//                /** @var BwaInfo $bwaInfo */
//                $bwaInfo = $this->getContainer()->get('core.service.bwa_info');
//                $bwaAge = $bwaInfo->getDomainCreation($domain);
//
//                $trustFlow = $majesticInfo->getTrustFlow($url);
//                $exchangeSite->setMajesticTrustFlow($trustFlow);
//                $refDomains = $majesticInfo->getRefDomains($domain);
//                $age = floatval(ExchangeSiteUtil::dateDifference(date("Y-m-d"), $bwaAge,'%y.%m'));
//                $credits = ExchangeSiteUtil::creditAlgo($trustFlow, $refDomains, $age);
//                $exchangeSite->setMaximumCredits($credits['cred'] * $creditCost);

                $exchangeSite->setActive($data['active']);
                $exchangeSite->setAcceptEref($data['accept_eref']);
                $exchangeSite->setAcceptWeb($data['accept_web']);
                $exchangeSite->setTags($data['tags']);
                $exchangeSite->setAcceptSelf($data['accept_self']);
                $exchangeSite->setMinWordsNumber($data['nb_mots_max']);
                $exchangeSite->setMaxLinksNumber($data['nb_liens_max']);
                $exchangeSite->setMinImagesNumber($data['nb_images_min']);
                $exchangeSite->setMaxImagesNumber($data['nb_images_min']);
                if(!empty($data['regle'])) {
                    $exchangeSite->setPublicationRules($data['regle']);
                }
                $exchangeSite->setTrustedWebmaster($data['showurltotrusted']);
                $exchangeSite->setApiKey($data['api_key']);
                $exchangeSite->setPluginUrl($data['plugin_url']);
                if(!empty($data['alexa'])){
                    $exchangeSite->setAlexaRank($data['alexa']);
                }
                if($data['age'] !== "0000-00-00"){
                    $exchangeSite->setAge(new \DateTime($data['age']));
                }
                $exchangeSite->setExternalId($data['id']);
                $exchangeSite->setSiteType(ExchangeSite::EXCHANGE_TYPE);

                $categoryRepository = $this->defaultEntityManager->getRepository(Category::class);
                if(!empty($data['cat_id1'])){
                    /** @var Category $category1 */
                    $category1 = $categoryRepository->find($data['cat_id1']);
                    if(!is_null($category1)){
                        $category1 = $this->defaultEntityManager->getRepository(Category::class)->findOneBy(['name' => $category1]);
                        if(!is_null($category1)){
                            $exchangeSite->addCategory($category1);
                        }
                    }
                }
                if(!empty($data['cat_id2'])){
                    /** @var Category $category2 */
                    $category2 = $categoryRepository->find($data['cat_id2']);
                    if(!is_null($category2)){
                        $category2 = $this->defaultEntityManager->getRepository(Category::class)->findOneBy(['name' => $category2]);
                        if(!is_null($category2)){
                            $exchangeSite->addCategory($category2);
                        }
                    }
                }

                $this->defaultEntityManager->persist($exchangeSite);
                $this->defaultEntityManager->flush();

                $this->output->writeln($this->formatMessage("exchangeSite EXTERNALID: {$exchangeId} SAVED ID: {$exchangeSite->getId()}"));
            }
        }

        return $exchangeSite;
    }

    /**
     * @param CopywritingOrder $copywritingOrder
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createCopywritingArticle($copywritingOrder, $keywords)
    {
        $sql = 'SELECT * FROM redaction_reports WHERE project_id = :order_id';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $params['order_id'] = $copywritingOrder->getExternalId();
        $stmt->execute($params);

        $report = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(!empty($report)){
            $this->output->writeln($this->formatMessage("copywritingArticle EXTERNALID: {$copywritingOrder->getExternalId()} STARTED"));
                $copywritingArticle = new CopywritingArticle();
                $copywritingArticle->setOrder($copywritingOrder);
                $copywritingArticle->setText($report['text']);
                $copywritingArticle->setMetaTitle($report['meta_title']);
                $copywritingArticle->setMetaDesc($report['meta_desc']);
                $copywritingArticle->setCorrectorEarn($report['corrector_earn']);
                $copywritingArticle->setWriterEarn($report['writer_earn']);
                $copywritingArticle->setFrontImage($report['front_image']);
                if(!empty($report['image_sources'])){
                    $imageSources = preg_split('/\r\n|\r|\n/', $report['image_sources']);
                    $copywritingArticle->setImageSources($imageSources);
                    $copywritingArticle->setImagesNumber(count($imageSources));
                }

                $stats = unserialize($report['report']);
                $copywritingArticle->setWordsNumber($stats['words_count']);
                if(isset($stats['H2_start'])){
                    $copywritingArticle->setHeaderTwoNumber($stats['H2_start']);
                }
                if(isset($stats['H3_start'])){
                    $copywritingArticle->setHeaderThreeNumber($stats['H3_start']);
                }
                if(isset($stats['words_used'])){
                    $missedKeywords = [];
                    foreach ($keywords as $keyword){
                        if(!in_array($keyword, $stats['words_used'])){
                            $missedKeywords[] = $keyword;
                        }
                    }
                    $copywritingArticle->setMissedKeywords($missedKeywords);
                }
                if(isset($stats['words_used_count'])){
                    $copywritingArticle->setKeywordsNumber($stats['words_used_count']);
                }
                if(isset($stats['seo_meta_title'])){
                    $copywritingArticle->setMetaTitleKeywords([$stats['seo_meta_title']]);
                }
                if(isset($stats['seo_H1_set'])){
                    $copywritingArticle->setHeaderOneKeywords([$stats['seo_H1_set']]);
                }
                if(isset($stats['seo_H2_set'])){
                    $copywritingArticle->setHeaderTwoKeywords([$stats['seo_H2_set']]);
                }
                if(isset($stats['seo_H3_set'])){
                    $copywritingArticle->setHeaderThreeKeywords([$stats['seo_H3_set']]);
                }
                if(isset($stats['images'])){
                    $copywritingArticle->setImagesNumber($stats['images']);
                }
        }else{
            $copywritingArticle = new CopywritingArticle();
            $copywritingArticle->setOrder($copywritingOrder);
        }

        $this->defaultEntityManager->persist($copywritingArticle);
        $this->defaultEntityManager->flush();
        $this->output->writeln($this->formatMessage("copywritingArticle EXTERNALID: {$copywritingOrder->getExternalId()} SAVED ID: {$copywritingArticle->getId()}"));
    }

    private function updateCopywritingArticles()
    {
        $countQuery = "SELECT COUNT(*) FROM redaction_reports";
        $stmt = $this->releaseEntityManager->getConnection()->prepare($countQuery);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $limit = 2000;
        $i = 0;
        if($count){
            while($i < $count) {
                $sql = "SELECT project_id, image_sources, convert(cast(convert(report using latin1) as binary) using utf8) as report FROM redaction_reports LIMIT $limit OFFSET $i";
                $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
                $stmt->execute();

                $reports = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $copywritingArticleRepository = $this->defaultEntityManager->getRepository(CopywritingArticle::class);

                foreach ($reports as $report) {
                    $copywritingArticle = $copywritingArticleRepository->findOneBy(["oldProjectId" => $report['project_id']]);
                    if ($copywritingArticle === null) {
                        continue;
                    }

                    if (!empty($report['image_sources'])) {
                        $imageSources = preg_split('/\r\n|\r|\n/', $report['image_sources']);
                        $copywritingArticle->setImageSources($imageSources);
                        $copywritingArticle->setImagesNumber(count($imageSources));
                    }

                    $stats = unserialize($report['report']);
                    $copywritingArticle->setWordsNumber($stats['words_count']);
                    if (isset($stats['H2_start'])) {
                        $copywritingArticle->setHeaderTwoNumber($stats['H2_start']);
                    }
                    if (isset($stats['H3_start'])) {
                        $copywritingArticle->setHeaderThreeNumber($stats['H3_start']);
                    }
                    if (isset($stats['words_used'])) {
                        $missedKeywords = [];
                        $keywords = $copywritingArticle->getOrder()->getKeywords();
                        /** @var CopywritingKeyword $keyword */
                        foreach ($keywords as $keyword){
                            if (!in_array($keyword->getWord(), $stats['words_used'])) {
                                $missedKeywords[] = $keyword->getWord();
                            }
                        }
                        $copywritingArticle->setMissedKeywords($missedKeywords);
                    }
                    if (isset($stats['words_used_count'])) {
                        $copywritingArticle->setKeywordsNumber($stats['words_used_count']);
                    }
                    if (isset($stats['seo_meta_title'])) {
                        $copywritingArticle->setMetaTitleKeywords([$stats['seo_meta_title']]);
                    }
                    if (isset($stats['seo_H1_set'])) {
                        $copywritingArticle->setHeaderOneKeywords([$stats['seo_H1_set']]);
                    }
                    if (isset($stats['seo_H2_set'])) {
                        $copywritingArticle->setHeaderTwoKeywords([$stats['seo_H2_set']]);
                    }
                    if (isset($stats['seo_H3_set'])) {
                        $copywritingArticle->setHeaderThreeKeywords([$stats['seo_H3_set']]);
                    }
                    if (isset($stats['images'])) {
                        $copywritingArticle->setImagesNumber($stats['images']);
                    }

                    $this->output->writeln($this->formatMessage("copywritingArticle PROJECTID: {$report['project_id']} UPDATED"));
                }
                $i += $limit;
                $this->defaultEntityManager->flush();
            }
            $this->defaultEntityManager->flush();
        }

    }

    /**
     * @param CopywritingOrder $copywritingOrder
     * @throws \Doctrine\DBAL\DBALException
     */
    private function saveCopywritingImages($copywritingOrder, $externalOrderId)
    {
        $sql = 'SELECT * FROM redaction_images WHERE project_id = :order_id';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $params['order_id'] = $externalOrderId;
        $stmt->execute($params);

        $imagesData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!is_null($imagesData) && $imagesData !== false){
            foreach ($imagesData as $imageData){
                if(mb_strlen($imageData['image_url']) > 240){
                    continue;
                }
                $image = new CopywritingImage();
                $image->setUrl($imageData['image_url']);
                $image->setAlt($imageData['alt']);
                $copywritingOrder->addImages($image);
            }
        }
    }

    /**
     * @param CopywritingOrder $copywritingOrder
     * @throws \Doctrine\DBAL\DBALException
     */
    private function saveCopywritingKeywords($copywritingOrder, $externalOrderId)
    {
        $sql = 'SELECT * FROM redaction_seo_words WHERE project_id = :order_id';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $params['order_id'] = $externalOrderId;
        $stmt->execute($params);

        $result = [];
        $keywordsData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!is_null($keywordsData) && $keywordsData !== false){
            foreach ($keywordsData as $keywordData){
                $keywords = explode(',', $keywordData['expression']);
                if(count($keywords) === 1){
                    $anotherExplode = explode('|', $keywordData['expression']);
                    if(count($anotherExplode) > 1){
                        $keywords = $anotherExplode;
                    }
                }
                foreach ($keywords as $keyword){
                    $copywritingKeyword = new CopywritingKeyword();
                    $trimedKeyword = trim($keyword);
                    $result[] = $trimedKeyword;
                    $copywritingKeyword->setWord($trimedKeyword);
                    $copywritingOrder->addKeywords($copywritingKeyword);
                }
            }
        }

        return $result;
    }

    private function saveLongCopywritingKeywords()
    {
        $sql = "SELECT project_id, convert(cast(convert(expression using latin1) as binary) using utf8) as expression FROM redaction_seo_words WHERE (expression REGEXP '.*(,|;|\\\|).*')";
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        //troubles with import this data
        //SELECT * FROM `redaction_seo_words` WHERE (expression NOT REGEXP ".*(,|;|\\|).*") and char_length(expression) > 255
        $keywordsData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!is_null($keywordsData) && $keywordsData !== false){
            foreach ($keywordsData as $keywordData){
                $copywritingOrder = $this->defaultEntityManager->getRepository(CopywritingOrder::class)->findOneBy([
                    'externalId' => $keywordData['project_id']
                ]);
                if($copywritingOrder === null){
                    continue;
                }
                $keywords = preg_split( "/(,|;|\\|)/", $keywordData['expression']);
                foreach ($keywords as $keyword){
                    if($keyword === "") continue;
                    $copywritingKeyword = new CopywritingKeyword();
                    $copywritingKeyword->setWord(trim($keyword));
                    $copywritingOrder->addKeywords($copywritingKeyword);
                }
                $this->defaultEntityManager->flush();
                $this->output->writeln($this->formatMessage("copywritingOrder EXTERNALID: {$keywordData['project_id']} UPDATED"));
            }
        }
    }

    private function createCopywrtingArticlesForOrderInProgress(){
        $orderRepository = $this->defaultEntityManager->getRepository(CopywritingOrder::class);

        $qb = $orderRepository->createQueryBuilder('co');
        $orders = $qb
            ->leftJoin('co.article', 'a')
            ->andWhere($qb->expr()->isNull('a.id'))
            ->andWhere($qb->expr()->eq("co.status", $qb->expr()->literal(CopywritingOrder::STATUS_PROGRESS)))
            ->getQuery()
            ->getResult();
        ;

        /** @var CopywritingOrder $order */
        foreach ($orders as $order){
            $article = new CopywritingArticle();
            $article->setOrder($order);
            $this->defaultEntityManager->persist($article);
        }

        $this->defaultEntityManager->flush();
    }

    private function getExchangeProposition($externalId)
    {
        $exchangeProposition = $this->defaultEntityManager->getRepository(ExchangeProposition::class)->findOneBy(['externalId' => $externalId]);
        if(!is_null($exchangeProposition)){
            $this->output->writeln($this->formatMessage("exchangeProposition EXTERNALID: $externalId FIND"));
        }

        if(is_null($exchangeProposition)){
            $sql = 'SELECT * FROM echanges_proposition WHERE id = :external_id';
            $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
            $params['external_id'] = $externalId;
            $stmt->execute($params);

            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            if(!empty($data)){
                $this->output->writeln($this->formatMessage("exchangeProposition EXTERNALID: {$data['id']} STARTED"));
                $exchangeProposition = new ExchangeProposition();
                $exchangeProposition->setUser($this->getPartner($data['from_user_id']));
                $exchangeProposition->setExchangeSite($this->getExchangeSite($data['to_site_id']));
                $exchangeProposition->setRedac($data['redac']);
                $exchangeProposition->setDocumentLink($data['doc_link']);
                $exchangeProposition->setDocumentImage($data['doc_img']);
                switch ($data['ech_status']){
                    case 10:
                        $exchangeProposition->setEchStatus(ExchangeProposition::STATUS_10);
                        break;
                    case 50:
                        $exchangeProposition->setEchStatus(ExchangeProposition::STATUS_50);
                        break;
                    case 100:
                        $exchangeProposition->setEchStatus(ExchangeProposition::STATUS_100);
                        break;
                    case 110:
                        $exchangeProposition->setEchStatus(ExchangeProposition::STATUS_110);
                        break;
                    case 200:
                        $exchangeProposition->setEchStatus(ExchangeProposition::STATUS_200);
                        break;
                    case 201:
                        $exchangeProposition->setEchStatus(ExchangeProposition::STATUS_201);
                        break;
                    default:
                        $exchangeProposition->setEchStatus(ExchangeProposition::STATUS_0);
                        break;
                }
                if (!empty($data['datetime_created'])) {
                    $createdAt = new \DateTime();
                    $createdAt->setTimestamp($data['datetime_created']);
                    $exchangeProposition->setCreatedAt($createdAt);
                }
                if (!empty($data['datetime_accepted'])) {
                    $acceptedAt = new \DateTime();
                    $acceptedAt->setTimestamp($data['datetime_accepted']);
                    $exchangeProposition->setAcceptedAt($acceptedAt);
                }
                if($data['page_publish'] !== 0){
                    $exchangeProposition->setPagePublish($data['page_publish']);
                }
                $exchangeProposition->setWordsNumber($data['nb_mots']);
                $exchangeProposition->setImagesNumber($data['nb_images']);
                $exchangeProposition->setLinksNumber($data['nb_liens']);
                if(!empty($data['plaintext'])){
                    $exchangeProposition->setPlaintext($data['plaintext']);
                }
                $exchangeProposition->setComments($data['comment']); //maybe it is wrong field
                if(!empty($data['check_links']) && $data['check_links'] != "N;"){
                    $newLinks = [];
                    $fixed_data = preg_replace_callback ( '!s:(\d+):"(.*?)";!', function($match) {
                        return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
                    },$data['check_links']);
                    try {
                        $oldLinks = unserialize($fixed_data);
                    }catch (\Exception $e){
                        $this->output->writeln("BAD ARRAY:  " . $fixed_data);
                        throw $e;
                    }
                    foreach ($oldLinks as $anchor => $url){
                        $link = ['anchor' => $anchor, 'url' => $url];
                        $newLinks[] = $link;
                    }
                    $exchangeProposition->setCheckLinks($newLinks);
                }
                $exchangeProposition->setViewed($data['viewed']);
                $exchangeProposition->setCredits($data['credit_cost']);
                if(!empty($data['mod_client'])) {
                    $exchangeProposition->setModificationComment($data['mod_client']);
                }
                switch ($data['mod_status']){
                    case 0:
                        $exchangeProposition->setModificationStatus(ExchangeProposition::STATUS_0);
                        break;
                    case 2:
                        $exchangeProposition->setModificationStatus(ExchangeProposition::MODIFICATION_STATUS_2);
                        break;
                    case 3:
                        $exchangeProposition->setModificationStatus(ExchangeProposition::MODIFICATION_STATUS_3);
                        break;
                }
                $exchangeProposition->setModificationClose($data['mod_close']);
                if(!empty($data['mod_refuse'])){
                    $exchangeProposition->setModificationRefuseComment($data['mod_refuse']);
                }
                $exchangeProposition->setRateStars($data['avis_note']);
                $exchangeProposition->setRateComment($data['avis_com']);
                $exchangeProposition->setInstructions($data['consigne']);
                $exchangeProposition->setIsSelf($data['self']);
//                $exchangeProposition->setNetlinkingProject($this->importNetlinkingProject($data['projet_id']));
                $exchangeProposition->setNetlinkingProject(null);
                $exchangeProposition->setExternalId($data['id']);

                $this->defaultEntityManager->persist($exchangeProposition);
                $this->defaultEntityManager->flush();
                $this->output->writeln($this->formatMessage("exchangeProposition EXTERNALID: {$exchangeProposition->getExternalId()} SAVED ID: {$exchangeProposition->getId()}"));
            }
        }

        return $exchangeProposition;
    }

    private function importNetlinkingProject($externalId = null)
    {
        if($externalId === null){
            $sql = 'SELECT COUNT(*) FROM projets';
            $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
            $stmt->execute();

            $projectsCount = $stmt->fetchColumn();
            $offset = 0;

            while($offset < $projectsCount){
                $sql = "SELECT * FROM projets ORDER BY id DESC LIMIT " . self::COUNT . " OFFSET $offset";
                $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
                $stmt->execute();

                $result = $stmt->fetchAll();

                foreach ($result as $item) {
                    $netlinkingProject = $this->defaultEntityManager->getRepository(NetlinkingProject::class)->findOneBy(['externalId' => $item['id']]);
                    if(is_null($netlinkingProject)){
                        $this->importOneNetlinkingProject($item);
                    }else{
                        $this->output->writeln($this->formatMessage("netlinkingProject EXTERNALID: {$externalId} FOUND"));
                    }
                }
                $offset += self::COUNT;
            }

            $this->output->writeln("Imported netlinking projects");
        }else{
            $netlinkingProject = $this->defaultEntityManager->getRepository(NetlinkingProject::class)->findOneBy(['externalId' => $externalId]);

            if(!is_null($netlinkingProject)){
                $this->output->writeln($this->formatMessage("netlinkingProject EXTERNALID: {$externalId} FOUND"));

                return $netlinkingProject;
            }
            if(is_null($netlinkingProject)){
                $sql = 'SELECT * FROM projets WHERE id = :external_id';
                $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
                $params['external_id'] = $externalId;
                $stmt->execute($params);

                $data = $stmt->fetch(\PDO::FETCH_ASSOC);

                return $this->importOneNetlinkingProject($data);
            }
        }
    }

    private function importOneNetlinkingProject($data)
    {
        if(!empty($data)){
            $this->output->writeln($this->formatMessage("netlinkingProject EXTERNALID: {$data['id']} STARTED"));
            $netlinkingProject = new NetlinkingProject();
            $netlinkingProject->setDirectoryList($this->getDirectoryList($data['annuaire']));
            $url = mb_strtolower($data['lien']);
            if(isset($url) && substr($url, 0, 4 ) !== "http"){
                $url .= "http://";
            };
            $netlinkingProject->setUrl($url);
            $netlinkingProject->setUser($this->getPartner($data['proprietaire']));

            $frequence = explode('x', $data['frequence']);
            $netlinkingProject->setFrequencyDirectory($frequence[0]);
            $netlinkingProject->setFrequencyDay($frequence[1]);
            if(!empty($data['consignes'])){
                $netlinkingProject->setComment($data['consignes']);
            }

            $sendTime = new \DateTime();
            $sendTime->setTimestamp($data['sendTime']);
            $netlinkingProject->setStartedAt($sendTime);
            $netlinkingProject->setCreatedAt($sendTime);
            if($data['created'] !== "0000-00-00 00:00:00"){
                $createdAt = new \DateTime($data['created']);
                $netlinkingProject->setCreatedAt($createdAt);
            }
            if(!empty($data['affectedBY'])){
                $netlinkingProject->setAffectedByUser($this->getPartner(intval($data['affectedBY'])));
            }
            if(!empty($data['affectedTO'])){
                $netlinkingProject->setAffectedToUser($this->getPartner(intval($data['affectedTO'])));
            }

            $affectedAt = new \DateTime();
            $affectedAt->setTimestamp($data['affectedTime']);
            $netlinkingProject->setAffectedAt($affectedAt);
            $netlinkingProject->setUpdatedAt($affectedAt);
            $netlinkingProject->setStatus(NetlinkingProject::STATUS_NO_START);
            if($data['affectedTO']){
                $netlinkingProject->setStatus(NetlinkingProject::STATUS_IN_PROGRESS);
            }elseif($data['adminApprouve']){
                $netlinkingProject->setStatus(NetlinkingProject::STATUS_WAITING);
            }
            if($data['over']){
                $netlinkingProject->setStatus(NetlinkingProject::STATUS_FINISHED);
            }
            $netlinkingProject->setExternalId($data['id']);

            $this->defaultEntityManager->persist($netlinkingProject);
            $this->defaultEntityManager->flush();
            $this->output->writeln($this->formatMessage("netlinkingProject EXTERNALID: {$netlinkingProject->getExternalId()} SAVED ID: {$netlinkingProject->getId()}"));

            return $netlinkingProject;
        }
    }

    private function getDirectoryList($externalId)
    {
        $directoryList = $this->defaultEntityManager->getRepository(DirectoriesList::class)->findOneBy(['externalId' => $externalId]);

        if(!is_null($directoryList)){
            $this->output->writeln($this->formatMessage("directoryList EXTERNALID: {$externalId} FOUND"));
        }
        if(is_null($directoryList)){
            $sql = 'SELECT * FROM annuaireslist WHERE id = :external_id';
            $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
            $params['external_id'] = $externalId;
            $stmt->execute($params);

            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            if(!empty($data)){
                $this->output->writeln($this->formatMessage("directoryList EXTERNALID: {$externalId} STARTED"));
                $directoryList = new DirectoriesList();
                $directoryList->setName($data['libelle']);
                if(!empty($data['proprietaire'])){
                    $directoryList->setUser($this->getPartner($data['proprietaire']));
                }
                if(!empty($data['annuairesList'])){
                    $directories = explode(';', $data['annuairesList']);
                    foreach ($directories as $directory){
                        if($directory !== "") {
                            $firstSymbol = $directory[0];
                            if ($firstSymbol === "E") {
                                $directoryList->addExchangeSite($this->getExchangeSite(substr($directory, 1)));
                            } else {
                                $directoryList->addDirectories($this->getDirectory($directory));
                            }
                        }
                    }
                }
                if(!empty($data['words_count'])){
                    $directoryList->setWordsCount($data['words_count']);
                }
                if(!empty($data['filter_config'])){
                    $directoryList->setFilter($data['filter_config']);
                }

                $createdAt = new \DateTime();
                $createdAt->setTimestamp($data['created']);
                $directoryList->setCreatedAt($createdAt);

                if(!empty($data['last_viewed'])){
                    $lastSeen = new \DateTime();
                    $lastSeen->setTimestamp($data['last_viewed']);
                    $directoryList->setLastSeen($lastSeen);
                }
                $directoryList->setEnabled($data['is_deleted']);
                $directoryList->setExternalId($externalId);

                $this->defaultEntityManager->persist($directoryList);
                $this->defaultEntityManager->flush();
                $this->output->writeln($this->formatMessage("directoryList EXTERNALID: {$directoryList->getExternalId()} SAVED ID: {$directoryList->getId()}"));
            }
        }

        return $directoryList;
    }

    private function updateDirectoryList()
    {
        $sql = "SELECT * FROM annuaireslist";
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll();

        foreach ($result as $item) {
            $directoryList = $this->defaultEntityManager->getRepository(DirectoriesList::class)->findOneBy(['externalId' => $item['id']]);
            if($directoryList === null) continue;
            $this->output->writeln($this->formatMessage("directoryList EXTERNALID: {$item['id']} STARTED {$item['id']}"));
            if(!empty($item['annuairesList'])){
                $directories = explode(';', $item['annuairesList']);
                foreach ($directories as $directory){
                    if($directory !== "") {
                        $directoryObject = $this->getDirectory($directory);
                        if($directoryObject !== null){
                            $directoryList->addDirectories($directoryObject);
                        }
                    }
                }
                if(rand(0, 100) === 0){
                    $this->defaultEntityManager->flush();
                }
            }
        }

        $this->defaultEntityManager->flush();
    }

    private function getDirectory($externalId)
    {
        $directory = $this->defaultEntityManager->getRepository(Directory::class)->findOneBy(['externalId' => $externalId]);

        if(!is_null($directory)){
            $this->output->writeln($this->formatMessage("directory EXTERNALID: {$externalId} FOUND"));
        }

        if(is_null($directory)){
            $sql = 'SELECT * FROM annuaires WHERE id = :external_id';
            $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
            $params['external_id'] = $externalId;
            $stmt->execute($params);

            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            if(!empty($data)){
                $this->output->writeln($this->formatMessage("directory EXTERNALID: {$externalId}"));
                $directory = new Directory();

                if(!empty($data['tarifW'])) {
                    $directory->setTariffExtraWebmaster($data['tarifW']);
                }
                if(!empty($data['tarifR'])) {
                    $directory->setTariffExtraSeo($data['tarifR']);
                }

                $directory
                    ->setName($data['annuaire'])
                    ->setPageRank($data['page_rank'])
                    ->setActive($data['active'])
                    ->setWebmasterAnchor($data['webmasterAncre'])
                ;

                if(!empty($data['webmasterPartenaire'])){
                    $directory->setWebmasterPartner($this->getPartner($data['webmasterPartenaire']));
                }
                if(!empty($data['consignes'])){
                    $directory->setInstructions($data['consignes']);
                }

                if(!empty($data['webmasterConsigne'])){
                    $directory->setWebmasterOrder($data['webmasterConsigne']);
                }

                if(!empty($data['WebPartenairePrice'])){
                    $directory->setTariffWebmasterPartner($data['WebPartenairePrice']);
                }

                $createdAt = new \DateTime($data['created']);
                $directory
                    ->setPersonalAccountWebmaster($data['ComptePersoWebmaster'])
                    ->setCreatedAt($createdAt)
                    ->setNddTarget($data['nddcible'])
                    ->setPageCount($data['page_count'])
                ;

                if(!empty($data['tf'])){
                    $directory->setMajesticTrustFlow($data['tf']);
                }
                if(!empty($data['ar'])){
                    $directory->setAlexaRank($data['ar']);
                }

                $age = new \DateTime();
                $age->setTimestamp($data['age']);
                $directory->setAge($age);

                if(!empty($data['tdr'])){
                    $directory->setTotalReferringDomain($data['tdr']);
                }

                if(!empty($data['tb'])){
                    $directory->setTotalBacklink($data['tb']);
                }

                if(!empty($data['tv'])){
                    $directory->setValidationTime($data['tv']);
                }

                if(!empty($data['tav'])){
                    $directory->setValidationRate($data['tav']);
                }

                if(!empty($data['link_submission'])){
                    $directory->setLinkSubmission($data['link_submission']);
                }

                if(!empty($data['vip_text'])){
                    $directory->setVipText($data['vip_text']);
                }

                if(!empty($data['min_words_count'])){
                    $directory->setMinWordsCount($data['min_words_count']);
                }

                if(!empty($data['max_words_count'])){
                    $directory->setMaxWordsCount($data['max_words_count']);
                }

                $directory
                    ->setAcceptInnerPages($data['accept_inner_pages'])
                    ->setAcceptLegalInfo($data['accept_legal_info'])
                    ->setAcceptCompanyWebsites($data['accept_company_websites'])
                    ->setVipState($data['vip_state'])
                    ->setExternalId($data['id'])
                ;

                $this->defaultEntityManager->persist($directory);
                $this->defaultEntityManager->flush();
                $this->output->writeln($this->formatMessage("directory EXTERNALID: {$directory->getExternalId()} SAVED ID: {$directory->getId()}"));
            }
        }

        return $directory;
    }

    private function importCopywritingLikes()
    {
        $sql = 'SELECT * FROM redaction_writers_likes ORDER BY project_id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)){
            foreach ($data as $item){
                $articleRating = new CopywritingArticleRating();

                $project = $this->importCopywriting($item['project_id']);
                $order = $project->getOrders()[0];

                if(is_null($order)){
                    $this->output->writeln("ERROR: order does't exist {$item['project_id']}");
                    continue;
                }
                $articleRating->setOrder($order);
                $articleRating->setValue($item['value']);
                if(!empty($item['webmaster_comment'])){
                    $articleRating->setComment($item['webmaster_comment']);
                }
                if(!empty($item['clicked_time'])) {
                    $createdAt = new \DateTime();
                    $createdAt->setTimestamp($item['clicked_time']);
                    $articleRating->setCreatedAt($createdAt);
                }

                $this->defaultEntityManager->persist($articleRating);
                $this->defaultEntityManager->flush();

                $this->output->writeln($this->formatMessage("copywritingLike ID: {$articleRating->getId()} SAVED"));
            }
        }
    }

    private function importInvoice()
    {
        $sql = 'SELECT * FROM factures ORDER BY id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)){
            foreach ($data as $item){
                $invoice = new Invoice();

                $user = $this->getPartner($item['user']);
                $invoice->setUser($user);
                $invoice->setAmount($item['amount']);
                $invoice->setVat($item['tva']);
                if(!empty($item['file'])) {
                    $invoice->setFile($item['file']); //ask saved in old format or new
                }else{
                    /** @var GenerateInvoiceService $generateInvoice */
                    $generateInvoice = $this->getContainer()->get("core.service.generate_invoice_service");
                    $generateInvoice->generateInvoice(
                        [
                            'total' => $item['amount'],
                            'time' => $item['time'],
                        ],$user);
                    continue;
                }

                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item['time']);
                $invoice->setNumber($createdAt->format('U'));
                $invoice->setCreatedAt($createdAt);

                $this->defaultEntityManager->persist($invoice);
                $this->defaultEntityManager->flush();

                $this->output->writeln($this->formatMessage("invoice ID: {$invoice->getId()} SAVED"));
            }
        }
    }

    private function importTransaction()
    {
        $sql = 'SELECT * FROM transaction ORDER BY id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)){
            foreach ($data as $item){
                $transaction = new Transaction();

                $transaction->setUser($this->getPartner($item['user_id']));
                $transaction->setDescription($item['details']);
                $transaction->setDebit($item['debit']);
                $transaction->setCredit($item['credit']);
                $transaction->setSolder($item['solde']);
                $transaction->setCreatedAt(new \DateTime($item['created_time']));

                $this->defaultEntityManager->persist($transaction);
                $this->defaultEntityManager->flush();

                $this->output->writeln($this->formatMessage("transaction ID: {$transaction->getId()} SAVED"));
            }
        }
    }

    private function importMessage()
    {
        $sql = 'SELECT * FROM messages ORDER BY id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)){
            foreach ($data as $item){
                $message = new Message();

                $message->setSubject($item['objet']);
                $message->setContent($item['content']);
                $message->setSendUser($this->getPartner($item['sender']));
                $message->setReceiveUser($this->getPartner($item['receiver']));
                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item['time']);
                $message->setCreatedAt($createdAt);
                $message->setReadAt($createdAt);
                $message->setIsRead($item['viewReceiver']);

                $this->defaultEntityManager->persist($message);
                $this->defaultEntityManager->flush();

                $this->output->writeln($this->formatMessage("message ID: {$message->getId()} SAVED"));
            }
        }
    }


    private function importJobs($externalId = null)
    {
        if($externalId === null){
            $sql = 'SELECT COUNT(*) FROM jobs';
            $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
            $stmt->execute();

            $count = $stmt->fetchColumn();
            $offset = 0;

            while($offset < $count){
                $sql = "SELECT * FROM jobs ORDER BY id DESC LIMIT " . self::COUNT . " OFFSET $offset";
                $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
                $stmt->execute();

                $result = $stmt->fetchAll();

                foreach ($result as $item) {
                    $job = $this->defaultEntityManager->getRepository(Job::class)->findOneBy(['externalId' => $item['id']]);
                    if(is_null($job)){
                        $this->importOneJob($item);
                    }else{
                        $this->output->writeln($this->formatMessage("Job EXTERNALID: {$externalId} FOUND"));
                    }
                }

                $this->defaultEntityManager->flush();
                $offset += self::COUNT;
            }

            $this->output->writeln("Jobs is imported");
        }
    }

    private function importOneJob($item)
    {
        $job = new Job();

        if(!empty($item['coutReferer'])){
            $job->setCostWriter($item['coutReferer']);
        }
        if(!empty($item['coutWebmaster'])){
            $job->setCostWebmaster($item['coutWebmaster']);
        }
        $job->setNetlinkingProject($this->importNetlinkingProject($item['siteID']));
        $job->setScheduleTask($this->importScheduleTask($item['annuaireID'], $item['siteID'], $item['created']));

        if(!empty($item['affectedto'])){
            $job->setAffectedToUser($this->getPartner($item['affectedto']));
        }

        switch ($item['adminApprouved']){
            case 2:
                $job->setApprovedStatus(Job::SUBMISSION_SUCCESS);
                break;
            case 3:
                $job->setApprovedStatus(Job::SUBMISSION_IMPOSSIBLE);
                break;
        }

        $affectedAt = new \DateTime();
        $affectedAt->setTimestamp($item['affectedTime']);
        $job->setAffectedAt($affectedAt);
        $job->setExternalId($item['id']);

        $submissionedAt = new \DateTime();
        $submissionedAt->setTimestamp($item['soumissibleTime']);
        $job->setCompletedAt($submissionedAt);


        $this->defaultEntityManager->persist($job);

        $this->output->writeln($this->formatMessage("job EXTERNALID: {$job->getExternalId()} SAVED ID: {$job->getId()}"));
    }

    private function importScheduleTask($annuaireID, $netlinkingProject, $created){
        $scheduleTask = new ScheduleTask();

        $firstSymbol = $annuaireID[0];
        if ($firstSymbol === "E") {
            $scheduleTask->setExchangeSite($this->getExchangeSite(substr($annuaireID, 1)));
        }else{
            $scheduleTask->setDirectory($this->getDirectory($annuaireID));
        }

        $scheduleTask->setNetlinkingProject($netlinkingProject);
        $startAt = new \DateTime();
        $startAt->setTimestamp($created);
        $scheduleTask->setStartAt($startAt);

        return $scheduleTask;
    }

    private function importRejectedJobs() //RUN AFTER IMPORT JOBS
    {
        $sql = 'SELECT * FROM jobs_rejected ORDER BY project_id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)) {
            foreach ($data as $item) {
                /** @var Job $job */
                $job = $this->defaultEntityManager->getRepository(Job::class)->findOneBy(
                    [
                        'netlinkingProject' => $this->importNetlinkingProject($item['project_id']),
                        'directory' => $this->getDirectory($item['annuaire_id']),
                    ]
                );

                $job->setRejectedAt((clone $job->getAffectedAt())->modify('+2 days'));

                $this->defaultEntityManager->persist($job);

                $this->output->writeln($this->formatMessage("job ID: {$job->getId()} REJECTED"));
            }
            $this->defaultEntityManager->flush();
        }
    }

    private function importAffiliation()
    {
        $sql = 'SELECT * FROM affiliation_affilies ORDER BY id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)) {
            foreach ($data as $item) {
                $affiliation = new Affiliation();

                $affiliation->setParent($this->getPartner($item['parrain']));
                $affiliation->setAffiliation($this->getPartner($item['affilie']));
                $affiliation->setTariff($item['amount']);

                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item['time']);
                $affiliation->setCreatedAt($createdAt);

                $this->defaultEntityManager->persist($affiliation);
                $this->output->writeln($this->formatMessage("affiliation ID: {$affiliation->getId()} SAVED"));
            }
            $this->defaultEntityManager->flush();
        }
    }

    private function importAffiliationClick()
    {
        $sql = 'SELECT * FROM affiliation_click ORDER BY id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)) {
            foreach ($data as $item) {
                $affiliationClick = new AffiliationClick();

                $affiliationClick->setUser($this->getPartner($item['parrain']));
                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item['time']);
                $affiliationClick->setCreatedAt($createdAt);

                $this->defaultEntityManager->persist($affiliationClick);
                $this->output->writeln($this->formatMessage("affiliationClick ID: {$affiliationClick->getId()} SAVED"));
            }
            $this->defaultEntityManager->flush();
        }
    }

    private function importAnchor()
    {
        $sql = 'SELECT * FROM ancres ORDER BY id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)) {
            foreach ($data as $item) {
                $anchor = new Anchor();

                $anchor->setNetlinkingProject($this->importNetlinkingProject($item['projetID']));

                $firstSymbol = $item['annuaireID'][0];
                if ($firstSymbol === "E") {
                    $anchor->setExchangeSite($this->getExchangeSite(substr($item['annuaireID'], 1)));
                } else {
                    $anchor->setDirectory($this->getDirectory($item['annuaireID']));
                }

                if(!empty($item['ancre'])){
                    $anchor->setName($item['ancre']);
                }

                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item['created']);
                $anchor->setCreatedAt($createdAt);

                $this->defaultEntityManager->persist($anchor);
                $this->output->writeln($this->formatMessage("anchor ID: {$anchor->getId()} SAVED"));
            }
            $this->defaultEntityManager->flush();
        }
    }

    private function importCommisions()
    {
        $sql = 'SELECT * FROM comissions ORDER BY id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)) {
            foreach ($data as $item) {
                $commision = new Comission();

                $commision->setNetlinkingProject($this->importNetlinkingProject($item['site_id']));
                $commision->setDirectory($this->getDirectory($item['annuaire_id']));
                $commision->setUser($this->getPartner($item['webmaster']));
                $commision->setAmount($item['amount']);

                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item['date']);
                $commision->setCreatedAt($createdAt);

                $this->defaultEntityManager->persist($commision);
                $this->output->writeln($this->formatMessage("commision ID: {$commision->getId()} SAVED"));
            }
            $this->defaultEntityManager->flush();
        }
    }

    private function importNetlinkingComments(){
        $sql = 'SELECT * FROM commentaires ORDER BY id DESC';
        $stmt = $this->releaseEntityManager->getConnection()->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($data)) {
            foreach ($data as $item) {
                $comment = new NetlinkingProjectComments();

                $comment->setNetlinkingProject($this->importNetlinkingProject($item['idprojet']));
                $comment->setDirectory($this->getDirectory($item['idannuaire']));
                $comment->setUser($this->getPartner($item['user']));
                if(!empty($item['com'])){
                    $comment->setComment($item['com']);
                }

                $createdAt = new \DateTime();
                $createdAt->setTimestamp($item['created']);
                $comment->setCreatedAt($createdAt);

                $this->defaultEntityManager->persist($comment);
                $this->output->writeln($this->formatMessage("comment ID: {$comment->getId()} SAVED"));
            }
            $this->defaultEntityManager->flush();
        }
    }

    private function formatMessage($message){
        $elements = explode(' ', $message);

        $resultString = "";

        $countSpace = 20;
        foreach ($elements as $key => $element){
            $rest = $countSpace - strlen($element);
            $resultString .= $element . str_repeat(" ", $rest);
            $countSpace = 13;
        }

        return $resultString;
    }
}
