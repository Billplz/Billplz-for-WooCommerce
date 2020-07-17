<?php

defined('ABSPATH') || exit;

class BillplzBankName
{
    public static function get()
    {
        $bank_name = array(
            'ABMB0212' => 'Alliance Bank Malaysia Berhad',
            'ABB0233' => 'Affin Bank Berhad',
            'ABB0234' => 'Affin Bank Berhad',
            'AMBB0209' => 'AmBank (M) Berhad',
            'BPMBMYKL' => 'AGROBANK',
            'BCBB0235' => 'CIMB Bank Berhad',
            'BIMB0340' => 'Bank Islam Malaysia Berhad',
            'BKRM0602' => 'Bank Kerjasama Rakyat Malaysia Berhad',
            'BMMB0341' => 'Bank Muamalat (Malaysia) Berhad',
            'BSN0601' => 'Bank Simpanan Nasional Berhad',
            'CIT0217' => 'Citibank Berhad',
            'HLB0224' => 'Hong Leong Bank Berhad',
            'HBMBMYKL' => 'HSBC Bank Malaysia Berhad',
            'HSBC0223' => 'HSBC Bank Malaysia Berhad',
            'KFH0346' => 'Kuwait Finance House',
            'MB2U0227' => 'Maybank2u / Malayan Banking Berhad',
            'MBBEMYKL' => 'Maybank2u / Malayan Banking Berhad',
            'MBB0227' => 'Maybank2E / Malayan Banking Berhad E',
            'MBB0228' => 'Maybank2E / Malayan Banking Berhad E',
            'OCBC0229' => 'OCBC Bank (Malaysia) Berhad',
            'PBB0233' => 'Public Bank Berhad',
            'RJHIMYKL' => 'AL RAJHI BANKING & INVESTMENT CORPORATION (MALAYSIA) BERHAD',
            'RHBBMYKL' => 'RHB Bank Berhad',
            'RHB0218' => 'RHB Bank Berhad',
            'SCB0216' => 'Standard Chartered Bank (Malaysia) Berhad',
            'UOB0226' => 'United Overseas Bank (Malaysia) Berhad',
            'UOB0229' => 'United Overseas Bank (Malaysia) Berhad',
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
            'BP-SGP01' => 'Senangpay'
        );
        asort($bank_name);
        return apply_filters('billplz_bank_name', $bank_name);
    }
}
