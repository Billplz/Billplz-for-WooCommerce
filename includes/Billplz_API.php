<?php

namespace Billplz;

class API
{
    private $connect;

    public function __construct(\Billplz\WPConnect $connect)
    {
        $this->connect = $connect;
    }

    public function setConnect(\Billplz\Connect $connect)
    {
        $this->connect = $connect;
    }

    public function getCollectionIndex(array $parameter = array())
    {
        return $this->connect->getCollectionIndex($parameter);
    }

    public function createCollection(string $parameter, array $optional = array())
    {
        return $this->connect->createCollection($parameter, $optional);
    }

    public function getCollection($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->getCollectionArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->getCollection($parameter);
        }

        throw new \Exception('Get Collection Error!');
    }

    public function createOpenCollection(array $parameter, array $optional = array())
    {
        $parameter['title'] = substr($parameter['title'], 0, 49);
        $parameter['description'] = substr($parameter['description'], 0, 199);

        if (intval($parameter['amount']) > 999999999) {
            throw new \Exception("Amount Invalid. Too big");
        }

        return $this->connect->createOpenCollection($parameter, $optional);
    }

    public function getOpenCollection($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->getOpenCollectionArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->getOpenCollection($parameter);
        }

        throw new \Exception('Get Open Collection Error!');
    }

    public function getOpenCollectionIndex(array $parameter = array())
    {
        return $this->connect->getOpenCollectionIndex($parameter);
    }

    public function createMPICollection($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->createMPICollectionArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->createMPICollection($parameter);
        }

        throw new \Exception('Create MPI Collection Error!');
    }

    public function getMPICollection($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->getMPICollectionArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->getMPICollection($parameter);
        }

        throw new \Exception('Get MPI Collection Error!');
    }

    public function createMPI(array $parameter, array $optional = array())
    {
        return $this->connect->createMPI($parameter, $optional);
    }

    public function getMPI($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->getMPIArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->getMPI($parameter);
        }

        throw new \Exception('Get MPI Error!');
    }

    public function deactivateCollection($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->deactivateColletionArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->deactivateCollection($parameter);
        }

        throw new \Exception('Deactivate Collection Error!');
    }

    public function activateCollection($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->deactivateColletionArray($parameter, 'activate');
        }
        if (\is_string($parameter)) {
            return $this->connect->deactivateCollection($parameter, 'activate');
        }

        throw new \Exception('Activate Collection Error!');
    }

    public function createBill(array $parameter, array $optional = array(), $sendCopy = '')
    {

        /* Email or Mobile must be set */
        if (empty($parameter['email']) && empty($parameter['mobile'])) {
            throw new \Exception("Email or Mobile must be set!");
        }

        /* Manipulate Deliver features to allow Email/SMS Only copy */
        if ($sendCopy === '0') {
            $optioonal['deliver'] = 'false';
        } elseif ($sendCopy === '1' && !empty($parameter['email'])) {
            $optional['deliver'] = 'true';
            unset($parameter['mobile']);
        } elseif ($sendCopy === '2' && !empty($parameter['mobile'])) {
            $optional['deliver'] = 'true';
            unset($parameter['email']);
        } elseif ($sendCopy === '3') {
            $optional['deliver'] = 'true';
        }

        /* Validate Mobile Number first */
        if (!empty($parameter['mobile'])) {
            /* Strip all unwanted character */
            $parameter['mobile'] = preg_replace('/[^0-9]/', '', $parameter['mobile']);

            /* Add '6' if applicable */
            $parameter['mobile'] = $parameter['mobile'][0] === '0' ? '6'.$parameter['mobile'] : $parameter['mobile'];

            /* If the number doesn't have valid formatting, reject it */
            /* The ONLY valid format '<1 Number>' + <10 Numbers> or '<1 Number>' + <11 Numbers> */
            /* Example: '60141234567' or '601412345678' */
            if (!preg_match('/^[0-9]{11,12}$/', $parameter['mobile'], $m)) {
                $parameter['mobile'] = '';
            }
        }

        /* Create Bills */
        $bill = $this->connect->createBill($parameter, $optional);
        if ($bill[0] === 200) {
            return $bill;
        }

        /* Check if Failed caused by wrong Collection ID */
        $collection = $this->toArray($this->getCollection($parameter['collection_id']));

        /* If doesn't exists or belong to another merchant */
        /* + In-case the collection id is an empty string */
        if ($collection[0] === 404 || $collection[0] === 401 || empty($parameter['collection_id'])) {
            /* Get All Active & Inactive Collection List */
            $collectionIndexActive = $this->toArray($this->getCollectionIndex(array('page'=>'1', 'status'=>'active')));
            $collectionIndexInactive = $this->toArray($this->getCollectionIndex(array('page'=>'1', 'status'=>'inactive')));

            /* If Active Collection not available but Inactive Collection is available */
            if (empty($collectionIndexActive[1]['collections']) && !empty($collectionIndexInactive[1]['collections'])) {
                /* Use inactive collection */
                $parameter['collection_id'] = $collectionIndexInactive[1]['collections'][0]['id'];
            }

            /* If there is Active Collection */
            elseif (!empty($collectionIndexActive[1]['collections'])) {
                $parameter['collection_id'] = $collectionIndexActive[1]['collections'][0]['id'];
            }

            /* If there is no Active and Inactive Collection */
            else {
                $collection = $this->toArray($this->createCollection('Payment for Purchase'));
                $parameter['collection_id'] = $collection[1]['id'];
            }
        }

        /* Create Bills */
        return $this->connect->createBill($parameter, $optional);
    }

    public function deleteBill($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->deleteBillArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->deleteBill($parameter);
        }

        throw new \Exception('Delete Bill Error!');
    }

    public function getBill($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->getBillArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->getBill($parameter);
        }

        throw new \Exception('Get Bill Error!');
    }

    public function bankAccountCheck($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->bankAccountCheckArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->bankAccountCheck($parameter);
        }

        throw new \Exception('Registration Check by Account Number Error!');
    }

    public function getTransactionIndex(string $id, array $parameter = array('page'=>'1'))
    {
        return $this->connect->getTransactionIndex($id, $parameter);
    }

    public function getPaymentMethodIndex($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->getPaymentMethodIndexArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->getPaymentMethodIndex($parameter);
        }

        throw new \Exception('Get Payment Method Index Error!');
    }

    public function updatePaymentMethod(array $parameter)
    {
        return $this->connect->updatePaymentMethod($parameter);
    }

    public function getBankAccountIndex(array $parameter = array('account_numbers'=>['0','1']))
    {
        return $this->connect->getBankAccountIndex($parameter);
    }

    public function getBankAccount($parameter)
    {
        if (\is_array($parameter)) {
            return $this->connect->getBankAccountArray($parameter);
        }
        if (\is_string($parameter)) {
            return $this->connect->getBankAccount($parameter);
        }

        throw new \Exception('Get Bank Account Error!');
    }

    public function createBankAccount(array $parameter)
    {
        return $this->connect->createBankAccount($parameter);
    }

    public function bypassBillplzPage(string $bill)
    {
        $bills = \json_decode($bill, true);
        if ($bills['reference_1_label']!=='Bank Code') {
            return \json_encode($bill);
        }

        $fpxBanks = $this->toArray($this->getFpxBanks());
        if ($fpxBanks[0] !== 200) {
            return \json_encode($bill);
        }

        $found = false;
        foreach ($fpxBanks[1]['banks'] as $bank) {
            if ($bank['name'] === $bills['reference_1']) {
                if ($bank['active']) {
                    $found = true;
                    break;
                }
                return \json_encode($bill);
            }
        }

        if ($found) {
            $bills['url'].='?auto_submit=true';
        }

        return json_encode($bills);
    }

    public function getFpxBanks()
    {
        return $this->connect->getFpxBanks();
    }

    public function toArray(array $json)
    {
        return $this->connect->toArray($json);
    }
}
