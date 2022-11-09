<?php

namespace CoreBundle\Utils;

/**
 * Class ExchangeSiteUtil
 *
 * @package CoreBundle\Utils
 */
class ExchangeSiteUtil
{

    /**
     * @return string
     */
    public static function genAccessToken()
    {
        if (@file_exists('/dev/urandom')) { // Get 100 bytes of random data
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 100);
        } elseif (function_exists('openssl_random_pseudo_bytes')) { // Get 100 bytes of pseudo-random data
            $bytes = openssl_random_pseudo_bytes(100, $strong);
            if (true === $strong && false !== $bytes) {
                $randomData = $bytes;
            }
        }
        // Last resort: mt_rand
        if (empty($randomData)) { // Get 108 bytes of (pseudo-random, insecure) data
            $randomData = mt_rand() . mt_rand() . mt_rand() . uniqid(mt_rand(), true) . microtime(true) . uniqid(
                    mt_rand(),
                    true
                );
        }

        return rtrim(strtr(base64_encode(hash('sha256', $randomData)), '+/', '-_'), '=');
    }

    /**
     * @param \DateTime $dateOne
     * @param \DateTime $dateTwo
     * @param string $differenceFormat
     *
     * @return string
     */
    public static function dateDifference($dateOne, $dateTwo, $differenceFormat = '%a')
    {
        $interval = $dateOne->diff($dateTwo);

        return $interval->format($differenceFormat);
    }

    /**
     * @param $trustFlow
     * @param $refDomains
     * @param $age
     *
     * @return array
     */
    public static function creditAlgo($trustFlow, $refDomains, $age)
    {
        //tf
        $tfi = $trustFlow;

        $trustFlow = $trustFlow / 10;

        $coa = 0.5;
        //coef age :
        if ($age < 1) {
            $coa = 0.5;
        } elseif ($age >= 1 && $age < 2) {
            $coa = 0.75;
        } elseif ($age >= 2 && $age < 3) {
            $coa = 1;
        } elseif ($age >= 3 && $age < 4) {
            $coa = 1.1;
        } elseif ($age >= 4 && $age < 5) {
            $coa = 1.2;
        } elseif ($age >= 5 && $age < 6) {
            $coa = 1.3;
        } elseif ($age >= 6 && $age < 7) {
            $coa = 1.4;
        } elseif ($age >= 7 && $age < 8) {
            $coa = 1.5;
        } elseif ($age >= 8 && $age < 9) {
            $coa = 1.6;
        } elseif ($age >= 9 && $age < 10) {
            $coa = 1.7;
        } elseif ($age >= 10 && $age < 11) {
            $coa = 1.8;
        } elseif ($age >= 11 && $age < 12) {
            $coa = 1.9;
        } elseif ($age >= 12) {
            $coa = 2;
        }

        if ($tfi > $refDomains) {
            $cred = round(($refDomains / 10) * $coa);

            if ($cred < 1) $cred = 1;

            return [
                'cred' => $cred,
                'message' => [
                    'domref' => '* DOMREF=' . $refDomains,
                    'coa' => '* COA=' . $coa,
                    'cred' => '* CRED MAX (DOMREF/10 * COA) =' . $cred,
                ]
            ];

        } else {

            $cod = 1;

            if ($tfi < 30) {
                $cod = 1;
            } elseif ($tfi < 40) {
                $cod = 1 + (0.1 * floor($refDomains / 100));
            } elseif ($tfi < 50) {
                $cod = 1 + (0.15 * floor($refDomains / 100));
            } elseif ($tfi < 60) {
                $cod = 1 + (0.2 * floor($refDomains / 100));
            } elseif ($tfi > 60) {
                $cod = 1 + (0.3 * floor($refDomains / 100));
            }
            if ($cod > 3) {
                $cod = 3;
            }

            $cred = round($trustFlow * $coa * $cod);

            if ($cred < 1) $cred = 1;

            return [
                'cred' => $cred,
                'message' => [
                    'trust_flow' => '* TF=' . $tfi,
                    'coa' => '* COA=' . $coa,
                    'cod' => '* COD=' . $cod,
                    'cred' => '* CRED MAX (TF/10 * COA * COD) =' . $cred,
                ]
            ];
        }

    }
}
