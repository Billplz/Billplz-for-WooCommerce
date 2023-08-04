<?php

defined('ABSPATH') || exit;

class BillplzBankName
{
    public static function get()
    {
        $bank_name = array(
            'ABMB0212' => 'allianceonline',
            'ABB0233' => 'affinOnline',
            'ABB0234' => 'affinOnline',
            'AMBB0209' => 'AmOnline',
            'AGRO01' => 'AGRONet',
            'BCBB0235' => 'CIMB Clicks',
            'BIMB0340' => 'Bank Islam Internet Banking',
            'BKRM0602' => 'i-Rakyat',
            'BMMB0341' => 'i-Muamalat',

            'BSN0601' => 'myBSN',
            'CIT0219' => 'Citibank Online',
            'HLB0224' => 'HLB Connect',
            'HSBC0223' => 'HSBC Online Banking',
            'KFH0346' => 'KFH Online',
            'MB2U0227' => 'Maybank2u',
            'MBB0227' => 'Maybank2E',
            'MBB0228' => 'Maybank2E',
            'OCBC0229' => 'OCBC Online Banking',
            'PBB0233' => 'PBe',
            'RHB0218' => 'RHB Now',
            'SCB0216' => 'SC Online Banking',
            'UOB0226' => 'UOB Internet Banking',
            'UOB0229' => 'UOB Internet Banking',
            'TEST0001' => 'FPX TEST 1',
            'TEST0002' => 'FPX TEST 2',
            'TEST0003' => 'FPX TEST 3',
            'TEST0004' => 'FPX TEST 4',
            'TEST0021' => 'FPX TEST 21',
            'TEST0022' => 'FPX TEST 22',
            'TEST0023' => 'FPX TEST 23',
            'BP-FKR01' => 'Billplz Simulator',
            'BP-PPL01' => 'PayPal',
            // 'BP-2C2P1' => 'e-pay',
            'BP-2C2PC' => 'Visa / Mastercard',
            // 'BP-2C2PU' => 'UnionPay',
            'BP-OCBC1' => 'Visa / Mastercard',
            'BP-BST01' => 'Boost',
            'BP-SGP01' => 'Visa / Mastercard',
            'BP-PAYDE' => 'Visa / Mastercard',
        );

        return $bank_name;
    }

    public static function getSwift()
    {
        return array(
            'PHBMMYKL' => 'Affin Bank Berhad',
            'BPMBMYKL' => 'AGROBANK / BANK PERTANIAN MALAYSIA BERHAD',
            'MFBBMYKL' => 'Alliance Bank Malaysia Berhad',
            'RJHIMYKL' => 'AL RAJHI BANKING &amp; INVESTMENT CORPORATION (MALAYSIA) BERHAD',
            'ARBKMYKL' => 'AmBank (M) Berhad',
            'BIMBMYKL' => 'Bank Islam Malaysia Berhad',
            'BKRMMYKL' => 'Bank Kerjasama Rakyat Malaysia Berhad',
            'BMMBMYKL' => 'Bank Muamalat (Malaysia) Berhad',
            'BSNAMYK1' => 'Bank Simpanan Nasional Berhad',
            'CIBBMYKL' => 'CIMB Bank Berhad',
            'CITIMYKL' => 'Citibank Berhad',
            'HLBBMYKL' => 'Hong Leong Bank Berhad',
            'HBMBMYKL' => 'HSBC Bank Malaysia Berhad',
            'KFHOMYKL' => 'Kuwait Finance House',
            'MBBEMYKL' => 'Maybank / Malayan Banking Berhad',
            'OCBCMYKL' => 'OCBC Bank (Malaysia) Berhad',
            'PBBEMYKL' => 'Public Bank Berhad',
            'RHBBMYKL' => 'RHB Bank Berhad',
            'SCBLMYKX' => 'Standard Chartered Bank (Malaysia) Berhad',
            'UOVBMYKL' => 'United Overseas Bank (Malaysia) Berhad',
        );
    }
}
