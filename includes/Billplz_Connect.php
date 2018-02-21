<?php

namespace Billplz;

class Connect
{
    private $api_key;
    private $x_signature_key;
    private $collection_id;

    private $process; //cURL or GuzzleHttp
    public $is_staging;
    public $url;

    public $header;

    const TIMEOUT = 10; //10 Seconds
    const PRODUCTION_URL = 'https://www.billplz.com/api/';
    const STAGING_URL = 'https://billplz-staging.herokuapp.com/api/';

    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;


        if (\class_exists('\GuzzleHttp\Client') && \class_exists('\GuzzleHttp\Exception\ClientException')) {
            $this->process = new \GuzzleHttp\Client();
            $this->header = array(
                'auth' => [$this->api_key, ''],
                'verify' => false
            );
        } else {
            $this->process = curl_init();
            $this->header = $api_key . ':';
            curl_setopt($this->process, CURLOPT_HEADER, 0);
            curl_setopt($this->process, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->process, CURLOPT_TIMEOUT, self::TIMEOUT);
            curl_setopt($this->process, CURLOPT_USERPWD, $this->header);
        }
    }

    public function setMode(bool $is_staging = false)
    {
        $this->is_staging = $is_staging;
        if ($is_staging) {
            $this->url = self::PRODUCTION_URL;
        } else {
            $this->url = self::STAGING_URL;
        }
    }

    public function detectMode()
    {
        $this->url = self::PRODUCTION_URL;
        $collection = $this->toArray($this->getCollectionIndex());
        if ($collection[0] === 200) {
            $this->is_staging = false;
            return $this;
        }
        $this->url = self::STAGING_URL;
        $collection = $this->toArray($this->getCollectionIndex());
        if ($collection[0] === 200) {
            $this->is_staging = true;
            return $this;
        }
        throw new \Exception('The API Key is not valid. Check your API Key');
    }

    public function getCollectionIndex(array $parameter = array())
    {
        $url = $this->url . 'v4/collections?'.http_build_query($parameter);

        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }
        return $return;
    }

    public function createCollection(string $title, array $optional = array())
    {
        $url = $this->url . 'v4/collections';

        $title = ['title' => $title];
        $data = array_merge($title, $optional);

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = $data;
            $return = $this->guzzleProccessRequest('POST', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function createOpenCollection(array $parameter, array $optional = array())
    {
        $url = $this->url . 'v4/open_collections';

        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}

        $data = array_merge($parameter, $optional);

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = $data;
            $return = $this->guzzleProccessRequest('POST', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getCollectionArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v4/collections/' . $id;
            if ($this->process instanceof \GuzzleHttp\Client) {
                $return = $this->guzzleProccessRequest('GET', $url, $this->header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 0);
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function getCollection(string $id)
    {
        $url = $this->url . 'v4/collections/'.$id;

        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getOpenCollectionArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v4/open_collections/'.$id;
            if ($this->process instanceof \GuzzleHttp\Client) {
                $return = $this->guzzleProccessRequest('GET', $url, $this->header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 0);
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function getOpenCollection(string $id)
    {
        $url = $this->url . 'v4/open_collections/'.$id;
        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getOpenCollectionIndex(array $parameter = array())
    {
        $url = $this->url . 'v4/open_collections?'.http_build_query($parameter);

        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }
        return $return;
    }

    public function createMPICollectionArray(array $parameter)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections';

        $return_array = array();

        foreach ($parameter as $title) {
            $data = ['title' => $title];

            if ($this->process instanceof \GuzzleHttp\Client) {
                $header = $this->header;
                $header['form_params'] = $data;
                $return = $this->guzzleProccessRequest('POST', $url, $header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function createMPICollection(string $title)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections';

        $data = ['title' => $title];

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = $data;
            $return = $this->guzzleProccessRequest('POST', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getMPICollectionArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v4/mass_payment_instruction_collections/'.$id;
            if ($this->process instanceof \GuzzleHttp\Client) {
                $return = $this->guzzleProccessRequest('GET', $url, $this->header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 0);
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function getMPICollection(string $id)
    {
        $url = $this->url . 'v4/mass_payment_instruction_collections/'.$id;
        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function createMPI(array $parameter, array $optional = array())
    {
        $url = $this->url . 'v4/mass_payment_instructions';

        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}

        $data = array_merge($parameter, $optional);

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = $data;
            $return = $this->guzzleProccessRequest('POST', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getMPIArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v4/mass_payment_instructions/'.$id;
            if ($this->process instanceof \GuzzleHttp\Client) {
                $return = $this->guzzleProccessRequest('GET', $url, $this->header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 0);
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function getMPI(string $id)
    {
        $url = $this->url . 'v4/mass_payment_instructions/'.$id;
        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public static function getXSignature(string $x_signature_key)
    {
        $signingString = '';

        if (isset($_GET['billplz']['id']) &&isset($_GET['billplz']['paid_at']) && isset($_GET['billplz']['paid']) && isset($_GET['billplz']['x_signature'])) {
            $data = array(
                'id' => $_GET['billplz']['id'] ,
                'paid_at' =>  $_GET['billplz']['paid_at'],
                'paid' => $_GET['billplz']['paid'],
                'x_signature' =>  $_GET['billplz']['x_signature']
            );
            $type = 'redirect';
        } elseif (isset($_POST['x_signature'])) {
            $data = array(
               'amount' => isset($_POST['amount']) ? $_POST['amount'] : '',
               'collection_id' => isset($_POST['collection_id']) ? $_POST['collection_id'] : '',
               'due_at' => isset($_POST['due_at']) ? $_POST['due_at'] : '',
               'email' => isset($_POST['email']) ? $_POST['email'] : '',
               'id' => isset($_POST['id']) ? $_POST['id'] : '',
               'mobile' => isset($_POST['mobile']) ? $_POST['mobile'] : '',
               'name' => isset($_POST['name']) ? $_POST['name'] : '',
               'paid_amount' => isset($_POST['paid_amount']) ? $_POST['paid_amount'] : '',
               'paid_at' => isset($_POST['paid_at']) ? $_POST['paid_at'] : '',
               'paid' => isset($_POST['paid']) ? $_POST['paid'] : '',
               'state' => isset($_POST['state']) ? $_POST['state'] : '',
               'url' => isset($_POST['url']) ? $_POST['url'] : '',
               'x_signature' => isset($_POST['x_signature']) ? $_POST['x_signature'] :'',
           );
            $type = 'callback';
        } else {
            return false;
        }

        foreach ($data as $key => $value) {
            if (isset($_GET['billplz']['id'])) {
                $signingString .= 'billplz'.$key . $value;
            } else {
                $signingString .= $key . $value;
            }
            if (($key === 'url' && isset($_POST['x_signature']))|| ($key === 'paid' && isset($_GET['billplz']['id']))) {
                break;
            } else {
                $signingString .= '|';
            }
        }

        /*
         * Convert paid status to boolean
         */
        $data['paid'] = $data['paid'] === 'true' ? true : false;

        $signedString = hash_hmac('sha256', $signingString, $x_signature_key);

        if ($data['x_signature'] === $signedString) {
            $data['type'] = $type;
            return $data;
        }

        throw new \Exception('X Signature Calculation Mismatch!');
    }

    public function deactivateColletionArray(array $parameter, string $option = 'deactivate')
    {
        $return_array = array();

        foreach ($parameter as $title) {
            $url = $this->url . 'v3/collections/'.$title.'/'.$option;

            if ($this->process instanceof \GuzzleHttp\Client) {
                $header = $this->header;
                $header['form_params'] = array();
                $return = $this->guzzleProccessRequest('POST', $url, $header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 1);
                curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query(array()));
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function deactivateCollection(string $title, string $option = 'deactivate')
    {
        $url = $this->url . 'v3/collections/'.$title.'/'.$option;

        $data = ['title' => $title];

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = array();
            $return = $this->guzzleProccessRequest('POST', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 1);
            curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query(array()));
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function createBill(array $parameter, array $optional = array())
    {
        $url = $this->url . 'v3/bills';

        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}

        $data = array_merge($parameter, $optional);

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = $data;
            $return = $this->guzzleProccessRequest('POST', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getBillArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v3/bills/'.$id;

            if ($this->process instanceof \GuzzleHttp\Client) {
                $header = $this->header;
                $header['form_params'] = array();
                $return = $this->guzzleProccessRequest('GET', $url, $header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 0);
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function getBill(string $id)
    {
        $url = $this->url . 'v3/bills/'.$id;

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = array();
            $return = $this->guzzleProccessRequest('GET', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function deleteBillArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v3/bills/'.$id;

            if ($this->process instanceof \GuzzleHttp\Client) {
                $header = $this->header;
                $header['form_params'] = array();
                $return = $this->guzzleProccessRequest('DELETE', $url, $header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_CUSTOMREQUEST, "DELETE");
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function deleteBill(string $id)
    {
        $url = $this->url . 'v3/bills/'.$id;

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = array();
            $return = $this->guzzleProccessRequest('DELETE', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_CUSTOMREQUEST, "DELETE");
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function bankAccountCheckArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v3/check/bank_account_number/'.$id;
            if ($this->process instanceof \GuzzleHttp\Client) {
                $return = $this->guzzleProccessRequest('GET', $url, $this->header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 0);
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function bankAccountCheck(string $id)
    {
        $url = $this->url . 'v3/check/bank_account_number/'.$id;
        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getPaymentMethodIndexArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v3/collections/'.$id.'/payment_methods';
            if ($this->process instanceof \GuzzleHttp\Client) {
                $return = $this->guzzleProccessRequest('GET', $url, $this->header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 0);
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function getPaymentMethodIndex(string $id)
    {
        $url = $this->url . 'v3/collections/'.$id.'/payment_methods';
        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getTransactionIndex(string $id, array $parameter)
    {
        $url = $this->url . 'v3/bills/'.$id.'/transactions?'.http_build_query($parameter);

        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function updatePaymentMethod(array $parameter)
    {
        if (!isset($parameter['collection_id'])) {
            throw new \Exception('Collection ID is not passed on updatePaymethodMethod');
        }
        $url = $this->url . 'v3/collections/'.$parameter['collection_id'].'/payment_methods';

        unset($parameter['collection_id']);
        $data = $parameter;

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = $data;
            $return = $this->guzzleProccessRequest('PUT', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($data));
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getBankAccountIndex(array $parameter)
    {
        if (!is_array($parameter['account_numbers'])) {
            throw new \Exception('Not valid account numbers.');
        }

        $parameter = http_build_query($parameter);
        $parameter = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $parameter);

        $url = $this->url . 'v3/bank_verification_services?'.$parameter;

        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function getBankAccountArray(array $parameter)
    {
        $return_array = array();

        foreach ($parameter as $id) {
            $url = $this->url . 'v3/bank_verification_services/'.$id;
            if ($this->process instanceof \GuzzleHttp\Client) {
                $return = $this->guzzleProccessRequest('GET', $url, $this->header);
            } else {
                curl_setopt($this->process, CURLOPT_URL, $url);
                curl_setopt($this->process, CURLOPT_POST, 0);
                $body = curl_exec($this->process);
                $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
                $return = array($header,$body);
            }
            array_push($return_array, $return);
        }

        return $return_array;
    }

    public function getBankAccount(string $id)
    {
        $url = $this->url . 'v3/bank_verification_services/'.$id;
        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    public function createBankAccount(array $parameter)
    {
        $url = $this->url . 'v3/bank_verification_services';

        if ($this->process instanceof \GuzzleHttp\Client) {
            $header = $this->header;
            $header['form_params'] = $parameter;
            $return = $this->guzzleProccessRequest('POST', $url, $header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POSTFIELDS, http_build_query($paraparameter));
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }
        return $return;
    }

    public function getFpxBanks()
    {
        $url = $this->url . 'v3/fpx_banks';
        if ($this->process instanceof \GuzzleHttp\Client) {
            $return = $this->guzzleProccessRequest('GET', $url, $this->header);
        } else {
            curl_setopt($this->process, CURLOPT_URL, $url);
            curl_setopt($this->process, CURLOPT_POST, 0);
            $body = curl_exec($this->process);
            $header = curl_getinfo($this->process, CURLINFO_HTTP_CODE);
            $return = array($header,$body);
        }

        return $return;
    }

    private function guzzleProccessRequest($requestType, $url, $header)
    {
        try {
            $response = $this->process->request($requestType, $url, $header);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
        } finally {
            $return = $response->getBody()->getContents();
        }
        return array($response->getStatusCode(),$return);
    }

    public function closeConnection()
    {
        if ($this->process instanceof \GuzzleHttp\Client) {
            // Do nothing
        } else {
            curl_close($this->process);
        }
    }

    public function toArray(array $json)
    {
        return array($json[0], \json_decode($json[1], true));
    }
}
