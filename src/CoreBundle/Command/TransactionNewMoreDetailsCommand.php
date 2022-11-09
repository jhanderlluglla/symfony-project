<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\ExchangeProposition;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TransactionNewMoreDetailsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:transaction_more_details_update')
        ;
    }

    /**
     * @param $value
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function prepareDetail($value)
    {
        if (is_array($value)) {
            if (isset($value["currency"]) && isset($value["value"])) {
                $value = $value["value"];
            } else {
                throw new \Exception('Undefined type');
            }
        }

        return str_replace([' ', '€'], '', $value);
    }

    /**
     * @param $details
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function prepareDetails($details)
    {
        $rename = ['exchangeSitePrice' => ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE];

        foreach ($rename as $oldName => $newName) {
            if (isset($details[$oldName])) {
                $details[$newName] = $details[$oldName];
                unset($details[$oldName]);
            }
        }
        $prepareKeys = [
            ExchangeProposition::TRANSACTION_DETAIL_COMMISSION,
            'wordsPrice',
            'imagesPrice',
            'expressArticlesPrice',
            'priceWriterCategory',
            'withdraw',
            'ereferer_commission',
            'earningForWords',
            'earningForImages',
            'earningForExpress',
            'earningForWriterCategory',
            'net_to_pay',
            'earningMalus',
            ExchangeProposition::TRANSACTION_DETAIL_EXCHANGE_SITE_PRICE,
            ExchangeProposition::TRANSACTION_DETAIL_WEBMASTER_ADDITIONAL_PAY,
            CopywritingOrder::TRANSACTION_DETAIL_REDACTION_PRICE
        ];

        foreach ($details as $key => $value) {
            if (!in_array($key, $prepareKeys)) {
                continue;
            }

            $details[$key] = floatval($this->prepareDetail($value));
        }

        return $details;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $connection = $em->getConnection();

        $query = $connection->prepare('SELECT * FROM `transaction`');
        $query->execute();

        foreach ($query->fetchAll() as $transaction) {
            if ($transaction['more_details'] == null && $transaction['number_of_articles'] === null) {
                continue;
            }

            try {
                $array = unserialize($transaction['more_details']);

                if ($transaction['number_of_articles'] !== null) {
                    if ($array === null) {
                        $array = [];
                    }
                    $array[CopywritingProject::TRANSACTION_DETAIL_NUMBER_OF_ARTICLES] = intval($transaction['number_of_articles']);
                }

                $query = $connection->prepare('UPDATE `transaction` SET `more_details` = :more_details WHERE id = ' . $transaction['id']);
                $query->bindValue('more_details', $array === null ? null : json_encode($this->prepareDetails($array)));
                $query->execute();
            } catch (\Exception $exception) {
            }
        }
    }
}
