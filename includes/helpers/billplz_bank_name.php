<?php

defined('ABSPATH') || exit;

class BillplzBankName
{
    public static function get()
    {
        $bank_name = array(
            'ABMB0212' => __( 'allianceonline', 'bfw' ),
            'ABB0233' => __( 'affinOnline', 'bfw' ),
            'ABB0234' => __( 'affinOnline', 'bfw' ),
            'AMBB0209' => __( 'AmOnline', 'bfw' ),
            'AGRO01' => __( 'AGRONet', 'bfw' ),
            'BCBB0235' => __( 'CIMB Clicks', 'bfw' ),
            'BIMB0340' => __( 'Bank Islam Internet Banking', 'bfw' ),
            'BKRM0602' => __( 'i-Rakyat', 'bfw' ),
            'BMMB0341' => __( 'i-Muamalat', 'bfw' ),
            'BSN0601' => __( 'myBSN', 'bfw' ),
            'CIT0219' => __( 'Citibank Online', 'bfw' ),
            'HLB0224' => __( 'HLB Connect', 'bfw' ),
            'HSBC0223' => __( 'HSBC Online Banking', 'bfw' ),
            'KFH0346' => __( 'KFH Online', 'bfw' ),
            'MB2U0227' => __( 'Maybank2u', 'bfw' ),
            'MBB0227' => __( 'Maybank2E', 'bfw' ),
            'MBB0228' => __( 'Maybank2E', 'bfw' ),
            'OCBC0229' => __( 'OCBC Online Banking', 'bfw' ),
            'PBB0233' => __( 'PBe', 'bfw' ),
            'RHB0218' => __( 'RHB Now', 'bfw' ),
            'SCB0216' => __( 'SC Online Banking', 'bfw' ),
            'UOB0226' => __( 'UOB Internet Banking', 'bfw' ),
            'UOB0229' => __( 'UOB Internet Banking', 'bfw' ),
            'TEST0001' => __( 'FPX TEST 1', 'bfw' ),
            'TEST0002' => __( 'FPX TEST 2', 'bfw' ),
            'TEST0003' => __( 'FPX TEST 3', 'bfw' ),
            'TEST0004' => __( 'FPX TEST 4', 'bfw' ),
            'TEST0021' => __( 'FPX TEST 21', 'bfw' ),
            'TEST0022' => __( 'FPX TEST 22', 'bfw' ),
            'TEST0023' => __( 'FPX TEST 23', 'bfw' ),
            'BP-FKR01' => __( 'Billplz Simulator', 'bfw' ),
            'BP-PPL01' => __( 'PayPal', 'bfw' ),
            // 'BP-2C2P1' => __( 'e-pay', 'bfw' ),
            'BP-2C2PC' => __( 'Visa / Mastercard', 'bfw' ),
            // 'BP-2C2PU' => __( 'UnionPay', 'bfw' ),
            'BP-OCBC1' => __( 'Visa / Mastercard', 'bfw' ),
            'BP-BST01' => __( 'Boost', 'bfw' ),
            'BP-SGP01' => __( 'Visa / Mastercard', 'bfw' ),
            'BP-PAYDE' => __( 'Visa / Mastercard', 'bfw' ),
        );

        return $bank_name;
    }

    public static function getSwift( $sandbox = false )
    {
        $banks = array();

        if ( $sandbox == true ) {
            $banks['DUMMYBANKVERIFIED'] = __( 'Billplz Dummy Bank Verified', 'bfw' );
        }

        $banks['PHBMMYKL'] = __( 'Affin Bank Berhad', 'bfw' );
        $banks['BPMBMYKL'] = __( 'AGROBANK / BANK PERTANIAN MALAYSIA BERHAD', 'bfw' );
        $banks['MFBBMYKL'] = __( 'Alliance Bank Malaysia Berhad', 'bfw' );
        $banks['RJHIMYKL'] = __( 'AL RAJHI BANKING &amp; INVESTMENT CORPORATION (MALAYSIA) BERHAD', 'bfw' );
        $banks['ARBKMYKL'] = __( 'AmBank (M) Berhad', 'bfw' );
        $banks['BIMBMYKL'] = __( 'Bank Islam Malaysia Berhad', 'bfw' );
        $banks['BKRMMYKL'] = __( 'Bank Kerjasama Rakyat Malaysia Berhad', 'bfw' );
        $banks['BMMBMYKL'] = __( 'Bank Muamalat (Malaysia) Berhad', 'bfw' );
        $banks['BSNAMYK1'] = __( 'Bank Simpanan Nasional Berhad', 'bfw' );
        $banks['CIBBMYKL'] = __( 'CIMB Bank Berhad', 'bfw' );
        $banks['CITIMYKL'] = __( 'Citibank Berhad', 'bfw' );
        $banks['HLBBMYKL'] = __( 'Hong Leong Bank Berhad', 'bfw' );
        $banks['HBMBMYKL'] = __( 'HSBC Bank Malaysia Berhad', 'bfw' );
        $banks['KFHOMYKL'] = __( 'Kuwait Finance House', 'bfw' );
        $banks['MBBEMYKL'] = __( 'Maybank / Malayan Banking Berhad', 'bfw' );
        $banks['OCBCMYKL'] = __( 'OCBC Bank (Malaysia) Berhad', 'bfw' );
        $banks['PBBEMYKL'] = __( 'Public Bank Berhad', 'bfw' );
        $banks['RHBBMYKL'] = __( 'RHB Bank Berhad', 'bfw' );
        $banks['SCBLMYKX'] = __( 'Standard Chartered Bank (Malaysia) Berhad', 'bfw' );
        $banks['UOVBMYKL'] = __( 'United Overseas Bank (Malaysia) Berhad', 'bfw' );

        return $banks;

    }
}
