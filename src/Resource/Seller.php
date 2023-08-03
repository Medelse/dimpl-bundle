<?php

namespace Medelse\DimplBundle\Resource;

use Medelse\DimplBundle\Resolver\Seller\SellerResolver;
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
    public const UPDATE_USER_URL = '/'.self::API_VERSION.'/sellers/{sellerId}';

    public function createSeller(array $data): array
    {
        return $this->sendRequestFormData(
            Request::METHOD_POST,
            self::CREATE_USER_URL,
            (new SellerResolver())->resolve($data)
        );
    }

    public function updateSeller(string $sellerId, array $data): array
    {
        $path = str_replace(
            '{sellerId}',
            $sellerId,
            self::UPDATE_USER_URL
        );

        return $this->sendRequestFormData(
            Request::METHOD_PUT,
            $path,
            (new SellerResolver())->resolve($data)
        );
    }
}
