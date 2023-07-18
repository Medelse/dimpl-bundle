<?php

namespace Medelse\DimplBundle\Resource;

use Medelse\DimplBundle\Resolver\Seller\CreateSellerResolver;
use Symfony\Component\HttpFoundation\Request;

class Seller extends Resource
{
    public const IDENTIFIER_SIREN = 'siren';
    public const IDENTIFIER_CIF = 'cif';
    public const IDENTIFIER_NIF = 'nif';
    public const IDENTIFIER_KVK = 'kvk';
    public const IDENTIFIER_HR = 'hr';
    public const IDENTIFIER_CHRN = 'chrn';
    public const IDENTIFIER_BERN = 'bern';
    public const IDENTIFIER_VAT = 'vat';

    public const CREATE_USER_URL = '/'.self::API_VERSION.'/sellers';

    public function createSeller(array $data): array
    {
        $createResolver = new CreateSellerResolver();

        return $this->sendRequestFormData(
            Request::METHOD_POST,
            self::CREATE_USER_URL,
            $createResolver->resolve($data)
        );
    }
}
