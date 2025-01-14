<?php

defined('ABSPATH') || exit;

class BillplzPaymentOption
{
    public static function getBanks()
    {
        $bank_name = array(
            'ABMB0212' => __( 'allianceonline', 'bfw' ),
            'ABB0233' => __( 'affinOnline', 'bfw' ),
            'ABB0234' => __( 'Affin Bank', 'bfw' ),
            'AMBB0209' => __( 'AmOnline', 'bfw' ),
            'AGRO01' => __( 'AGRONet', 'bfw' ),
            'BCBB0235' => __( 'CIMB Clicks', 'bfw' ),
            'BIMB0340' => __( 'Bank Islam Internet Banking', 'bfw' ),
            'BKRM0602' => __( 'i-Rakyat', 'bfw' ),
            'BMMB0341' => __( 'i-Muamalat', 'bfw' ),
            'BOCM01' => __( 'Bank of China', 'bfw' ),
            'BSN0601' => __( 'myBSN', 'bfw' ),
            'CIT0219' => __( 'Citibank Online', 'bfw' ),
            'HLB0224' => __( 'HLB Connect', 'bfw' ),
            'HSBC0223' => __( 'HSBC Online Banking', 'bfw' ),
            'KFH0346' => __( 'KFH Online', 'bfw' ),
            'MB2U0227' => __( 'Maybank2u', 'bfw' ),
            'MBB0228' => __( 'Maybank2E', 'bfw' ),
            'OCBC0229' => __( 'OCBC Online Banking', 'bfw' ),
            'PBB0233' => __( 'PBe', 'bfw' ),
            'RHB0218' => __( 'RHB Now', 'bfw' ),
            'SCB0216' => __( 'SC Online Banking', 'bfw' ),
            'UOB0226' => __( 'UOB Internet Banking', 'bfw' ),
            'UOB0229' => __( 'UOB Bank', 'bfw' ),
            'TEST0001' => __( 'FPX TEST 1', 'bfw' ),
            'TEST0002' => __( 'FPX TEST 2', 'bfw' ),
            'TEST0003' => __( 'FPX TEST 3', 'bfw' ),
            'TEST0004' => __( 'FPX TEST 4', 'bfw' ),
            'TEST0021' => __( 'FPX TEST 21', 'bfw' ),
            'TEST0022' => __( 'FPX TEST 22', 'bfw' ),
            'TEST0023' => __( 'FPX TEST 23', 'bfw' ),
            'BP-FKR01' => __( 'Billplz Simulator', 'bfw' ),
            'BP-BILLPLZ1' => __( 'Visa / Mastercard (Billplz)', 'bfw' ),
            'BP-PPL01' => __( 'PayPal', 'bfw' ),
            'BP-OCBC1' => __( 'Visa / Mastercard', 'bfw' ),
            'BP-2C2P1' => __( 'e-pay', 'bfw' ),
            'BP-2C2PC' => __( 'Visa / Mastercard', 'bfw' ),
            'BP-2C2PU' => __( 'UnionPay', 'bfw' ),
            'BP-2C2PGRB' => __( 'Grab', 'bfw' ),
            'BP-2C2PGRBPL' => __( 'GrabPayLater', 'bfw' ),
            'BP-2C2PATOME' => __( 'Atome', 'bfw' ),
            'BP-2C2PBST' => __( 'Boost', 'bfw' ),
            'BP-2C2PTNG' => __( 'TnG', 'bfw' ),
            'BP-2C2PSHPE' => __( 'Shopee Pay', 'bfw' ),
            'BP-2C2PSHPQR' => __( 'Shopee Pay QR', 'bfw' ),
            'BP-2C2PIPP' => __( 'IPP', 'bfw' ),
            'BP-BST01' => __( 'Boost', 'bfw' ),
            'BP-TNG01' => __( 'TouchNGo E-Wallet', 'bfw' ),
            'BP-SGP01' => __( 'Senangpay', 'bfw' ),
            'BP-BILM1' => __( 'Visa / Mastercard', 'bfw' ),
            'BP-RZRGRB' => __( 'Grab', 'bfw' ),
            'BP-RZRBST' => __( 'Boost', 'bfw' ),
            'BP-RZRTNG' => __( 'TnG', 'bfw' ),
            'BP-RZRPAY' => __( 'RazerPay', 'bfw' ),
            'BP-RZRMB2QR' => __( 'Maybank QR', 'bfw' ),
            'BP-RZRWCTP' => __( 'WeChat Pay', 'bfw' ),
            'BP-RZRSHPE' => __( 'Shopee Pay', 'bfw' ),
            'BP-MPGS1' => __( 'MPGS', 'bfw' ),
            'BP-CYBS1' => __( 'Secure Acceptance', 'bfw' ),
            'BP-EBPG1' => __( 'Visa / Mastercard', 'bfw' ),
            'BP-EBPG2' => __( 'AMEX', 'bfw' ),
            'BP-PAYDE' => __( 'Paydee', 'bfw' ),
            'BP-MGATE1' => __( 'Visa / Mastercard / AMEX', 'bfw' ),
            'B2B1-ABB0235' => __( 'AFFINMAX', 'bfw' ),
            'B2B1-ABMB0213' => __( 'Alliance BizSmart', 'bfw' ),
            'B2B1-AGRO02' => __( 'AGRONetBIZ', 'bfw' ),
            'B2B1-AMBB0208' => __( 'AmAccess Biz', 'bfw' ),
            'B2B1-BCBB0235' => __( 'BizChannel@CIMB', 'bfw' ),
            'B2B1-BIMB0340' => __( 'Bank Islam eBanker', 'bfw' ),
            'B2B1-BKRM0602' => __( 'i-bizRAKYAT', 'bfw' ),
            'B2B1-BMMB0342' => __( 'iBiz Muamalat', 'bfw' ),
            'B2B1-BNP003' => __( 'BNP Paribas', 'bfw' ),
            'B2B1-CIT0218' => __( 'CitiDirect BE', 'bfw' ),
            'B2B1-DBB0199' => __( 'Deutsche Bank Autobahn', 'bfw' ),
            'B2B1-HLB0224' => __( 'HLB ConnectFirst', 'bfw' ),
            'B2B1-HSBC0223' => __( 'HSBCnet', 'bfw' ),
            'B2B1-KFH0346' => __( 'KFH Online', 'bfw' ),
            'B2B1-MBB0228' => __( 'Maybank2E', 'bfw' ),
            'B2B1-OCBC0229' => __( 'Velocity@ocbc', 'bfw' ),
            'B2B1-PBB0233' => __( 'PBe', 'bfw' ),
            'B2B1-PBB0234' => __( 'PB enterprise', 'bfw' ),
            'B2B1-RHB0218' => __( 'RHB Reflex', 'bfw' ),
            'B2B1-SCB0215' => __( 'SC Straight2Bank', 'bfw' ),
            'B2B1-TEST0021' => __( 'SBI Bank A', 'bfw' ),
            'B2B1-TEST0022' => __( 'SBI Bank B', 'bfw' ),
            'B2B1-TEST0023' => __( 'SBI Bank C', 'bfw' ),
            'B2B1-UOB0228' => __( 'UOB BIBPlus', 'bfw' ),
        );

        return $bank_name;
    }

    public static function getSwiftBanks( bool $sandbox = false )
    {
        $banks = array();

        if ( $sandbox === true ) {
            $banks['DUMMYBANKVERIFIED'] = __( 'Billplz Dummy Bank Verified', 'bfw' );
        }

        $banks['PHBMMYKL'] = __( 'Affin Bank Berhad', 'bfw' );
        $banks['AGOBMYKL'] = __( 'AGROBANK / BANK PERTANIAN MALAYSIA BERHAD', 'bfw' );
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
