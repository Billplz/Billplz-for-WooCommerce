<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

if (!class_exists('Billplz')) {

    class Billplz {

        public static $version = 3.04;
        var $array, $obj, $url, $id, $deliverLevel, $errorMessage;
        private $api_key_status = false;

        /*
         * API Key & X Signature Key may changed anytime
         * Provided if API Key and X Signature not provided
         */
        public static $api_key = '4e49de80-1670-4606-84f8-2f1d33a38670';
        public static $x_signature = 'S-0Sq67GFD9Y5iXmi5iXMKsA';

        public function __construct($api_key = '') {
            $this->array = array();
            $this->obj = new BillplzAction;
            if (empty($api_key)) {
                $this->obj->setAPI(self::$api_key);
            } else {
                $this->obj->setAPI($api_key);
            }
        }

        /*
         * Calling it with empty parameter will result to staging API Key
         * Default: Staging API Key
         */

        public function setAPIKey($api_key = '') {
            if (empty($api_key)) {
                $this->obj->setAPI(self::$api_key);
            } else {
                $this->obj->setAPI($api_key);
            }
            return $this;
        }

        public function getCollectionIndex($page = '1', $mode = '', $status = null) {

            /*
             * Identify mode if not supplied
             */

            if (empty($mode)) {
                $mode = $this->check_api_key();
            }

            $this->obj->setAction('GETCOLLECTIONINDEX');

            $this->obj->setURL($mode);
            $array = array (
                'page' => $page,
                'status' => $status,
            );
            $data = $this->obj->curl_action($array);

            return $data;
        }

        public static function getRedirectData($signkey = '') {

            if (empty($signkey)) {
                $signkey = self::$x_signature;
            }

            $data = array (
                'id' => isset($_GET['billplz']['id']) ? $_GET['billplz']['id'] : self::throwException('Billplz ID is not supplied'),
                'paid_at' => isset($_GET['billplz']['paid_at']) ? $_GET['billplz']['paid_at'] : self::throwException('Please enable Billplz XSignature Payment Completion'),
                'paid' => isset($_GET['billplz']['paid']) ? $_GET['billplz']['paid'] : self::throwException('Please enable Billplz XSignature Payment Completion'),
                'x_signature' => isset($_GET['billplz']['x_signature']) ? $_GET['billplz']['x_signature'] : self::throwException('Please enable Billplz XSignature Payment Completion'),
            );
            $preparedString = '';
            foreach ($data as $key => $value) {
                $preparedString .= 'billplz' . $key . $value;
                if ($key === 'paid') {
                    break;
                } else {
                    $preparedString .= '|';
                }
            }
            $generatedSHA = hash_hmac('sha256', $preparedString, $signkey);

            /*
             * Convert paid status to boolean
             */
            $data['paid'] = $data['paid'] === 'true' ? true : false;

            if ($data['x_signature'] === $generatedSHA) {
                return $data;
            } else {
                self::throwException('Data has been tempered');
            }
        }

        public static function throwException($message) {
            throw new Exception($message);
        }

        public static function getCallbackData($signkey = '') {

            if (empty($signkey)) {
                $signkey = self::$x_signature;
            }

            $data = array (
                'amount' => isset($_POST['amount']) ? $_POST['amount'] : self::throwException('Amount is not supplied'),
                'collection_id' => isset($_POST['collection_id']) ? $_POST['collection_id'] : self::throwException('Collection ID is not supplied'),
                'due_at' => isset($_POST['due_at']) ? $_POST['due_at'] : '',
                'email' => isset($_POST['email']) ? $_POST['email'] : '',
                'id' => isset($_POST['id']) ? $_POST['id'] : self::throwException('Billplz ID is not supplied'),
                'mobile' => isset($_POST['mobile']) ? $_POST['mobile'] : '',
                'name' => isset($_POST['name']) ? $_POST['name'] : self::throwException('Payer Name is not supplied'),
                'paid_amount' => isset($_POST['paid_amount']) ? $_POST['paid_amount'] : '',
                'paid_at' => isset($_POST['paid_at']) ? $_POST['paid_at'] : '',
                'paid' => isset($_POST['paid']) ? $_POST['paid'] : self::throwException('Paid status is not supplied'),
                'state' => isset($_POST['state']) ? $_POST['state'] : self::throwException('State is not supplied'),
                'url' => isset($_POST['url']) ? $_POST['url'] : self::throwException('URL is not supplied'),
                'x_signature' => isset($_POST['x_signature']) ? $_POST['x_signature'] : self::throwException('X Signature is not enabled'),
            );
            $preparedString = '';
            foreach ($data as $key => $value) {
                $preparedString .= $key . $value;
                if ($key === 'url') {
                    break;
                } else {
                    $preparedString .= '|';
                }
            }
            $generatedSHA = hash_hmac('sha256', $preparedString, $signkey);

            /*
             * Convert paid status to boolean
             */
            $data['paid'] = $data['paid'] === 'true' ? true : false;

            if ($data['x_signature'] === $generatedSHA) {
                return $data;
            } else {
                self::throwException('Data has been tempered');
            }
        }

        /*
         * Return true if delete bill success
         * Return false if delete bill not success
         */

        public function deleteBill($bill_id, $mode = '') {

            /*
             * Identify mode if not supplied
             */

            if (empty($mode)) {
                $mode = $this->check_api_key();
            }
            $this->obj->setAction('DELETE');
            $this->obj->setURL($mode, $bill_id);
            $data = $this->obj->curl_action();

            if (empty($data)) {
                return true;
            }
            
            /*
             * In case of the system admin changing Billplz Account, 
             * the bills that trying to be deleted not in the new account,
             * think it as success
             */
            
            if ($data['type']==='RecordNotFound'){
                return true;
            }
            
            return false;
        }

        public function checkMobileNumber($mobile) {
            $mobile = preg_replace("/[^0-9]/", "", $mobile);
            $custTel = $mobile;
            $custTel2 = substr($mobile, 0, 1);
            if ($custTel2 == '+') {
                $custTel3 = substr($mobile, 1, 1);
                if ($custTel3 != '6') {
                    $custTel = "+6" . $mobile;
                }
            } else if ($custTel2 == '6') {
                
            } else {
                if ($custTel != '') {
                    $custTel = "+6" . $mobile;
                }
            } return $custTel;
        }

        public function setCollection($collection_id) {
            $this->array['collection_id'] = $collection_id;
            return $this;
        }

        public function setName($name) {
            $this->array['name'] = $name;
            return $this;
        }

        public function setEmail($email) {
            $this->array['email'] = $email;
            return $this;
        }

        public function setMobile($mobile) {
            $this->array['mobile'] = $this->checkMobileNumber($mobile);
            return $this;
        }

        public function setAmount($amount) {
            $this->array['amount'] = $amount * 100;
            return $this;
        }

        public function setDeliver($deliver) {
            /*
             * '0' => No Notification
             * '1' => Email Notification
             * '2' => SMS Notification
             * '3' => Email & SMS Notification
             * 
             * However, if the setting is SMS and mobile phone is not given,
             * the Email value should be used and set the delivery to false.
             */
            $this->deliverLevel = $deliver;
            $this->array['deliver'] = $deliver != '0' ? true : false;
            return $this;
        }

        public function setReference_1($reference_1) {
            if (!empty($reference_1)) {
                $this->array['reference_1'] = substr($reference_1, 0, 119);
            }
            return $this;
        }

        public function setReference_2($reference_2) {
            if (!empty($reference_2)) {
                $this->array['reference_2'] = substr($reference_2, 0, 119);
            }
            return $this;
        }

        public function setDescription($description) {
            $this->array['description'] = substr($description, 0, 199);
            return $this;
        }

        public function setPassbackURL($callback_url, $redirect_url = '') {
            $this->array['redirect_url'] = $redirect_url;
            $this->array['callback_url'] = $callback_url;
            return $this;
        }

        public function setReference_1_Label($label) {
            if (!empty($label)) {
                $this->array['reference_1_label'] = substr($label, 0, 19);
            }
            return $this;
        }

        public function setReference_2_Label($label) {
            if (!empty($label)) {
                $this->array['reference_2_label'] = substr($label, 0, 19);
            }
            return $this;
        }

        public function create_collection($title = 'Payment For Purchase', $mode = '') {

            /*
             * Identify mode if not supplied
             */

            if (empty($mode)) {
                $mode = $this->check_api_key();
            }

            $this->obj->setAction('COLLECTIONS');

            $this->obj->setURL($mode);
            $data = array (
                'title' => $title
            );
            $collection = $this->obj->curl_action($data);
            return $collection['id'];
        }

        /*
         * Determine the API Key is belong to Production or Staging
         * Else, exit the program.
         */

        public function check_api_key() {
            $this->obj->setAction('GETCOLLECTIONINDEX');
            $array = array(
                'page' => '1',
                'status' => null,
            );
            $this->obj->setURL('Production');

            $status = $this->obj->curl_action($array);
            if (isset($status['collections'])) {
                $this->api_key_status = true;
                return 'Production';
            }
            $this->obj->setURL('Staging');
            $status = $this->obj->curl_action($array);

            if (isset($status['collections'])) {
                $this->api_key_status = true;
                return 'Staging';
            } else {
                $this->setAPIKey();
                $this->api_key_status = false;
                return 'Staging';
            }
        }

        public function get_api_key_status() {
            return $this->api_key_status;
        }

        public function check_collection_id($collection_id, $mode = '') {

            /*
             * Identify mode if not supplied
             */

            if (empty($mode)) {
                $mode = $this->check_api_key();
            }

            $this->obj->setAction('CHECKCOLLECTION');
            $this->obj->setURL($mode);
            $data = array (
                'id' => $collection_id
            );
            $status = $this->obj->curl_action($data);
            if (isset($status['id'])) {
                if ($status['id'] == $collection_id) {
                    return true;
                }
            }
            return false;
        }

        /*
         * Return the first active collection on first page.
         * If none, return the first collection of inactive collection.
         * 
         * If collection is not created yet, create one
         */

        public function get_active_collection($data) {
            if (empty($data['collections'])) {
                $collection_id = $this->create_collection();
            } else {
                for ($i = 0; $i < sizeof($data['collections']); $i++) {
                    if ($data['collections'][$i]['status'] == 'active') {
                        $collection_id = $data['collections'][$i]['id'];
                        break;
                    } else {
                        $collection_id = $data['collections'][0]['id'];
                    }
                }
            }
            return $collection_id;
        }

        public function get_transaction_index($bill_id, $page = '1', $status = 'completed', $mode = '') {
            /*
             * Identify mode if not supplied
             */

            if (empty($mode)) {
                $mode = $this->check_api_key();
            }

            $this->obj->setAction('GETTRANSACTIONINDEX');

            $this->obj->setURL($mode, $bill_id);
            $array = array (
                'page' => $page,
                'status' => $status,
            );
            $data = $this->obj->curl_action($array);

            return $data;
        }

        public function get_bill_paid_time($bill_id, $mode = '') {
            $array = $this->get_transaction_index($bill_id, $mode);

            foreach ($array['transactions'] as $transactions) {
                if ($transactions['status'] === 'completed') {
                    return strtotime($transactions['completed_at']) + (60 * 60 * 8);
                }
            }

            return '';
        }

        public function create_bill($checkCollection = false, $mode = '') {
            /*
             * Identify mode if not supplied
             */

            if (empty($mode)) {
                $mode = $this->check_api_key();
            }

            /*
             * Check Collection ID that has been entered is valid or not
             * If invalid, unset and auto-assign collection id
             */

            if ($checkCollection && isset($this->array['collection_id'])) {
                $status = $this->check_collection_id($this->array['collection_id'], $mode);
                if (!$status)
                    unset($this->array['collection_id']);
            }

            /*
             * Check wether the collection id is not set
             * If not set, get collection id
             */

            if (!isset($this->array['collection_id'])) {
                $collectionData = $this->getCollectionIndex($mode);

                $this->array['collection_id'] = $this->get_active_collection($collectionData);
            }

            $this->obj->setAction('CREATE');
            $this->obj->setURL($mode);

            /*
             * 1. Check deliverLevel. If 1 (Email only), unset mobile
             * 2. Check deliverLevel. If 2 (SMS only), unset Email
             * 3. Create Bills.
             * 4. If the bills failed to be created:
             * 5. Check if 0 (No Notification), unset Mobile
             * 5. Check if 1(Email only), unset Email, set Mobile, deliver to false
             * 6. Check if 2(SMS only), unset Mobile, set Email deliver to false
             * 7. Check if 3, unset Mobile.
             * 8. Still failed? Return false.
             * 9. Ok. Return $this.
             */

            if ($this->deliverLevel == '1') {
                $mobile = $this->array['mobile'];
                unset($this->array['mobile']);
            } else if ($this->deliverLevel == '2') {
                $email = $this->array['email'];
                unset($this->array['email']);
            }

            $data = $this->obj->curl_action($this->array);
            if (isset($data['error'])) {
                if ($this->deliverLevel == '1') {
                    unset($this->array['email']);
                    $this->array['mobile'] = $mobile;
                    $this->array['deliver'] = false;
                } else if ($this->deliverLevel == '2') {
                    unset($this->array['mobile']);
                    $this->array['email'] = $email;
                    $this->array['deliver'] = false;
                } else {
                    unset($this->array['mobile']);
                }
                $data = $this->obj->curl_action($this->array);
            }

            if (isset($data['error'])) {
                $this->errorMessage = $data['error']['type'] . ' ' . print_r($data['error']['message'], true);
                return false;
            }
            $this->url = $data['url'];
            $this->id = $data['id'];
            return $this;
        }

        public function getURL() {
            return $this->url;
        }

        public function getErrorMessage() {
            return $this->errorMessage;
        }

        public function getID() {
            return $this->id;
        }

        /*
         * Get Bills 
         */

        public function check_bill($bill_id, $mode = '') {

            /*
             * Identify mode if not supplied
             */

            if (empty($mode)) {
                $mode = $this->check_api_key();
            }
            $this->obj->setAction('CHECK');
            $this->obj->setURL($mode, $bill_id);
            $data = $this->obj->curl_action();
            return $data;
        }

    }

    class BillplzAction {

        var $url, $action, $curldata, $api_key;
        public static $production = 'https://www.billplz.com/api/v3/';
        public static $staging = 'https://billplz-staging.herokuapp.com/api/v3/';

        public function setAPI($api_key) {
            $this->api_key = $api_key;
            return $this;
        }

        public function setAction($action) {
            $this->action = $action;
            return $this;
        }

        public function setURL($mode, $id = '') {
            if ($mode == 'Staging') {
                $this->url = self::$staging;
            } elseif ($mode == 'Production') {
                $this->url = self::$production;
            } else {
                self::throwException('Invalid API Key Provided');
            }
            if ($this->action == 'DELETE' || $this->action == 'CHECK') {
                $this->url .= 'bills/' . $id;
            } elseif ($this->action == 'CREATE') {
                $this->url .= 'bills/';
            } else if ($this->action == 'GETCOLLECTIONINDEX') {
                $this->url .= 'collections';
            } else if ($this->action == 'GETTRANSACTIONINDEX') {
                $this->url .= 'bills/' . $id . '/transactions';
            } else { //COLLECTIONS or CHECKCOLLECTION
                $this->url .= 'collections/';
            }
            return $this;
        }

        public function curl_action($data = '') {

            if ($this->action == 'GETCOLLECTIONINDEX' || $this->action == 'GETTRANSACTIONINDEX') {
                $this->url .= '?page=' . $data['page'] . '&status=' . $data['status'];
            } else if ($this->action == 'CHECKCOLLECTION') {
                $this->url .= $data['id'];
            }

            $client = new Client();

            /*
             * Determine request type 
             * Action Available:
             * DELETE (DELETE)
             * CHECK (GET)
             * CREATE (POST)
             * GETCOLLECTIONINDEX (GET)
             * CHECKCOLLECTION (POST)
             * COLLECTIONS (POST)
             * 
             */

            if ($this->action == 'DELETE') {
                $reqType = 'DELETE';
            } else if ($this->action == 'CHECK' || $this->action == 'GETCOLLECTIONINDEX' || $this->action == 'CHECKCOLLECTION' || $this->action == 'GETTRANSACTIONINDEX') {
                $reqType = 'GET';
            } else {
                $reqType = 'POST';
            }

            $preparedHeader = array (
                'auth' => [$this->api_key, ''],
                'verify' => false,
            );

            if ($this->action == 'CREATE' || $this->action == 'COLLECTIONS') {
                $preparedHeader['form_params'] = $data;
            }
            try {
                $response = $client->request($reqType, $this->url, $preparedHeader);
            } catch (ClientException $e) {
                $response = $e->getResponse();
            } finally {
                $contents = $response->getBody()->getContents();
            }

            $this->curldata = json_decode($contents, true);

            return $this->curldata;
        }

    }

}
