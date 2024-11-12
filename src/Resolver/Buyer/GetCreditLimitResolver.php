<?php

namespace Medelse\DimplBundle\Resolver\Buyer;

use Medelse\DimplBundle\Resource\Buyer;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GetCreditLimitResolver
{
    private const ID_FIELDS = [
        'identifierType',
        'identifier',
    ];


    public function resolve(array $data): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptionsResolver($resolver);
        $data = $resolver->resolve($data);

        return $data;
    }

    private function configureOptionsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefined(self::ID_FIELDS);
        $resolver->setRequired(self::ID_FIELDS);

        $resolver
            ->setAllowedTypes('identifierType', ['string'])
            ->setAllowedValues('identifierType', function ($value) {
                return in_array(
                    $value,
                    [
                        Buyer::IDENTIFIER_SIREN,
                        Buyer::IDENTIFIER_CIF,
                        Buyer::IDENTIFIER_NIF,
                        Buyer::IDENTIFIER_KVK,
                        Buyer::IDENTIFIER_HR,
                        Buyer::IDENTIFIER_CHRN,
                        Buyer::IDENTIFIER_BERN,
                        Buyer::IDENTIFIER_VAT,
                    ]
                );
            })
            ->setAllowedTypes('identifier', ['string', 'numeric'])
            ->setNormalizer('identifier', function (Options $options, $value) {
                if (Buyer::IDENTIFIER_SIREN === $options['identifierType']) {
                    return is_string($value) ? str_replace(' ', '', $value) : $value;
                }

                return $value;
            })
        ;
    }
}
