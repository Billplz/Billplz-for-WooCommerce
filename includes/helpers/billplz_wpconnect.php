<?php

defined('ABSPATH') || exit;

class BillplzWooCommerceWPConnect
{
    private $api_key;
    private $x_signature_key;
    private $collection_id;

    private $process;
    public $is_staging;
    public $detect_mode;
    public $url;
    public $webhook_rank;

    public $header;

    const TIMEOUT = 10; //10 Seconds
    const PRODUCTION_URL = 'https://www.billplz.com/api/';
    const STAGING_URL = 'https://www.billplz-sandbox.com/api/';

    private static $instance;

    public static function get_instance() {
      if (null === self::$instance) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    private function __clone() {}

    public function set_api_key($api_key, $is_staging = false)
    {
        $this->api_key = $api_key;
        $this->setStaging($is_staging);

        $this->header = array(
            'Authorization' => 'Basic ' . base64_encode($this->api_key . ':'),
        );
    }

    public function setStaging($is_staging = false)
    {
        $this->is_staging = $is_staging;
        if ($is_staging) {
            $this->url = self::STAGING_URL;
        } else {
            $this->url = self::PRODUCTION_URL;
        }
    }

    public function detectMode()
    {
        $this->url = self::PRODUCTION_URL;
        $this->detect_mode = true;
        return $this;
    }

    public function getWebhookRank()
    {
        $url = $this->url . 'v4/webhook_rank';

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';
        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getCollectionIndex($parameter = array())
    {
        $url = $this->url . 'v4/collections?' . http_build_query($parameter);

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function createCollection($title, $optional = array())
    {
        $url = $this->url . 'v4/collections';

        $body = http_build_query(array('title' => $title));
        if (isset($optional['split_header'])) {
            $split_header = http_build_query(array('split_header' => $optional['split_header']));
        }

        $split_payments = array();
        if (isset($optional['split_payments'])) {
            foreach ($optional['split_payments'] as $param) {
                $split_payments[] = http_build_query($param);
            }
        }

        if (!empty($split_payments)) {
            $body .= '&' . implode('&', $split_payments);
            if (!empty($split_header)) {
                $body .= '&' . $split_header;
            }
        }

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = $body;
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function createOpenCollection($parameter, $optional = array())
    {
        $url = $this->url . 'v4/open_collections';

        $body = http_build_query($parameter);
        if (isset($optional['split_header'])) {
            $split_header = http_build_query(array('split_header' => $optional['split_header']));
        }

        $split_payments = array();
        if (isset($optional['split_payments'])) {
            foreach ($optional['split_payments'] as $param) {
                $split_payments[] = http_build_query($param);
            }
        }

        if (!empty($split_payments)) {
            unset($optional['split_payments']);
            $body .= '&' . implode('&', $split_payments);
            if (!empty($split_header)) {
                unset($optional['split_header']);
                $body .= '&' . $split_header;
            }
        }

        if (!empty($optional)) {
            $body .= '&' . http_build_query($optional);
        }

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = $body;
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getCollection($id)
    {
        $url = $this->url . 'v4/collections/' . $id;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getOpenCollection($id)
    {
        $url = $this->url . 'v4/open_collections/' . $id;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getOpenCollectionIndex($parameter = array())
    {
        $url = $this->url . 'v4/open_collections?' . http_build_query($parameter);

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function createMPICollection($title)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections';

        $data = array('title' => $title);

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = http_build_query($data);
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getMPICollection($id)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections/' . $id;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function createMPI($parameter, $optional = array())
    {
        $url = $this->url . 'v4/mass_payment_instructions';

        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}

        $data = array_merge($parameter, $optional);

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = http_build_query($data);
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getMPI($id)
    {
        $url = $this->url . 'v4/mass_payment_instructions/' . $id;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = http_build_query($data);
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public static function buildSourceString($data, $prefix = '')
    {
        uksort($data, function ($a, $b) {
            $a_len = strlen($a);
            $b_len = strlen($b);
            $result = strncasecmp($a, $b, min($a_len, $b_len));
            if ($result === 0) {
                $result = $b_len - $a_len;
            }
            return $result;
        });
        $processed = [];
        foreach ($data as $key => $value) {
            if ($key === 'x_signature') {
                continue;
            }
     
            if (is_array($value)) {
                $processed[] = self::buildSourceString($value, $key);
            } else {
                $processed[] = $prefix . $key . stripslashes($value);
            }
        }
        return implode('|', $processed);
    }

    public static function getXSignature($x_signature_key)
    {
        $data = array();

        if (isset($_GET['billplz']['x_signature'])) {
            $keys = array('id', 'paid_at', 'paid', 'transaction_id', 'transaction_status', 'x_signature');

            foreach ($keys as $key){
                if (isset($_GET['billplz'][$key])){
                    $data['billplz'][$key] = $_GET['billplz'][$key];
                }
            } 
            $type = 'redirect';
        } elseif (isset($_POST['x_signature'])) {
            $keys = array('amount', 'collection_id', 'due_at', 'email', 'id', 'mobile', 'name', 'paid_amount', 'transaction_id', 'transaction_status', 'paid_at', 'paid', 'state', 'url', 'x_signature');
            foreach ($keys as $key){
                if (isset($_POST[$key])){
                    $data[$key] = $_POST[$key];
                }
            }
            $type = 'callback';
        } else {
            throw new \Exception('X Signature on Payment Completion not activated.');
        }

        $signing = self::buildSourceString($data);

        if ($type == 'redirect'){
            $data = $data['billplz'];
        }

        /*
         * Convert paid status to boolean
         */
        $data['paid'] = $data['paid'] === 'true' ? true : false;

        $signed = hash_hmac('sha256', $signing, $x_signature_key);

        if ($data['x_signature'] === $signed) {
            $data['type'] = $type;
            return $data;
        }

        throw new \Exception('X Signature Calculation Mismatch!');
    }

    public function deactivateCollection($title, $option = 'deactivate')
    {
        $url = $this->url . 'v3/collections/' . $title . '/' . $option;

        $data = array('title' => $title);

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = http_build_query(array());
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function createBill($parameter, $optional = array())
    {
        $url = $this->url . 'v3/bills';

        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}

        $data = array_merge($parameter, $optional);

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = http_build_query($data);
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getBill($id)
    {
        $url = $this->url . 'v3/bills/' . $id;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function deleteBill($id)
    {
        $url = $this->url . 'v3/bills/' . $id;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = http_build_query(array());
        $wp_remote_data['method'] = 'DELETE';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function bankAccountCheck($id)
    {
        $url = $this->url . 'v3/check/bank_account_number/' . $id;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getPaymentMethodIndex($id)
    {
        $url = $this->url . 'v3/collections/' . $id . '/payment_methods';

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getTransactionIndex($id, $parameter)
    {
        $url = $this->url . 'v3/bills/' . $id . '/transactions?' . http_build_query($parameter);

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function updatePaymentMethod($parameter)
    {
        if (!isset($parameter['collection_id'])) {
            throw new \Exception('Collection ID is not passed on updatePaymethodMethod');
        }
        $url = $this->url . 'v3/collections/' . $parameter['collection_id'] . '/payment_methods';

        unset($parameter['collection_id']);
        $body = array();
        foreach ($parameter['payment_methods'] as $param) {
            $body[] = http_build_query($param);
        }

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = implode('&', $body);
        $wp_remote_data['method'] = 'PUT';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getBankAccountIndex($parameter)
    {
        if (!is_array($parameter['account_numbers'])) {
            throw new \Exception('Not valid account numbers.');
        }

        $parameter = http_build_query($parameter);
        $parameter = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $parameter);

        $url = $this->url . 'v3/bank_verification_services?' . $parameter;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getBankAccount($id)
    {
        $url = $this->url . 'v3/bank_verification_services/' . $id;

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function createBankAccount($parameter)
    {
        $url = $this->url . 'v3/bank_verification_services';

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = http_build_query($parameter);
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getFpxBanks()
    {
        $url = $this->url . 'v3/fpx_banks';

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function getPaymentGateways()
    {
        $url = $this->url . 'v4/payment_gateways';

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['method'] = 'GET';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function toArray($json)
    {
        return array($json[0], \json_decode($json[1], true));
    }
}

$GLOBALS['bfw_connect'] = BillplzWooCommerceWPConnect::get_instance();