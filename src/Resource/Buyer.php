<?php

namespace Medelse\DimplBundle\Resource;

use Medelse\DimplBundle\Resolver\Buyer\GetCreditLimitResolver;
use Symfony\Component\HttpFoundation\Request;

class Buyer extends Resource
{
    public const IDENTIFIER_SIREN = 'siren';
    public const IDENTIFIER_CIF = 'cif';
    public const IDENTIFIER_NIF = 'nif';
    public const IDENTIFIER_KVK = 'kvk';
    public const IDENTIFIER_HR = 'hr';
    public const IDENTIFIER_CHRN = 'chrn';
    public const IDENTIFIER_BERN = 'bern';
    public const IDENTIFIER_VAT = 'vat';

    public const GET_CREDIT_LIMIT = '/'.self::API_VERSION.'/eligibility/buyer';

    public function getCreditLimit(string $siren): int
    {
        $data = [
            'identifierType' => self::IDENTIFIER_SIREN,
            'identifier' => $siren,
        ];

        (new GetCreditLimitResolver())->resolve($data);

        $result = $this->sendGetRequest(self::GET_CREDIT_LIMIT, $data);

        if (is_array($result) && isset($result['creditLimit'])) {
            return $result['creditLimit'];
        }

        return 0;
    }
}
