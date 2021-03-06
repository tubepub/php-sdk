<?php

namespace ApiVideo\Client\Api;

use ApiVideo\Client\Model\Account as AccountModel;
use ApiVideo\Client\Model\Quota;
use ApiVideo\Client\Model\Term;
use DateTimeImmutable;
use Exception;

class Account extends BaseApi
{

    /**
     * @return AccountModel|null
     */
    public function get()
    {
        $response = $this->browser->get('/account');
        if (!$response->isSuccessful()) {
            $this->registerLastError($response);

            return null;
        }

        return $this->unmarshal($response);
    }


    /**
     * @param array $data
     * @return AccountModel
     * @throws Exception
     */
    protected function cast(array $data)
    {
        $account = new AccountModel();

        $quota                 = new Quota();
        $quota->quotaUsed      = $data['quota']['quotaUsed'];
        $quota->quotaRemaining = $data['quota']['quotaRemaining'];
        $quota->quotaTotal     = $data['quota']['quotaTotal'];

        $term          = new Term();
        $term->startAt = new DateTimeImmutable($data['startAt']);
        $term->endAt   = new DateTimeImmutable($data['endAt']);

        $account->quota = $quota;
        $account->term  = $term;

        return $account;
    }
}
