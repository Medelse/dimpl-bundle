<?php

namespace Medelse\DimplBundle\Resolver\Seller;

use Medelse\DimplBundle\Resource\Seller;
use Medelse\DimplBundle\Tool\ArrayFormatter;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SellerResolver
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    public function resolve(array $data): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptionsResolver($resolver);
        $data = $resolver->resolve($data);

        return ArrayFormatter::removeNullValues(
            [
                'ownerMobilePhone' => $data['phone'],
                'ownerEmail' => $data['email'],
                'ownerFirstName' => $data['givenName'],
                'ownerLastName' => $data['familyName'],
                'ownerNationality' => $data['nationality'],
                'ownerBirthDate' => $data['birthDate'],
                'ownerBirthCity' => $data['birthCity'],
                'ownerBirthCountry' => $data['birthCountry'],
                'ownerHomeAddress' => $data['addressFirst'],
                'ownerHomeCity' => $data['addressCity'],
                'ownerHomePostCode' => $data['addressPostal'],
                'ownerHomeCountry' => $data['addressCountry'],
                'identifierType' => $data['identifierType'],
                'identifier' => $data['identifier'],
                'iban' => $data['iban'],
                'ownerIdFile' => new DataPart(
                    $data['idFileFront']['document'],
                    $data['idFileFront']['fileName'],
                    $data['idFileFront']['contentType']
                ),
                'ownerIdVerso' => empty($data['idFileBack']) ? null : new DataPart(
                    $data['idFileBack']['document'],
                    $data['idFileBack']['fileName'],
                    $data['idFileBack']['contentType']
                ),
                'dimplTermsAcceptationDateTime' => $data['termsAcceptationDate'],
            ]
        );
    }

    private function configureOptionsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefined([
            'phone',
            'email',
            'givenName',
            'familyName',
            'nationality',
            'birthDate',
            'birthCity',
            'birthCountry',
            'addressFirst',
            'addressCity',
            'addressPostal',
            'addressCountry',
            'identifierType',
            'identifier',
            'iban',
            'idFileFront',
            'idFileBack',
            'termsAcceptationDate',
        ]);

        $resolver->setRequired([
            'phone',
            'email',
            'givenName',
            'familyName',
            'identifierType',
            'identifier',
            'iban',
            'idFileFront',
            'termsAcceptationDate',
        ]);

        $resolver
            ->setAllowedTypes('phone', ['string'])
            ->setAllowedTypes('email', ['string'])
            ->setAllowedValues('email', function ($value) {
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            })
            ->setAllowedTypes('givenName', ['string'])
            ->setAllowedTypes('familyName', ['string'])
            ->setAllowedTypes('nationality', ['null', 'string'])
            ->setAllowedTypes('birthDate', ['null', \DateTimeInterface::class])
            ->setNormalizer('birthDate', function (Options $options, $value) {
                if ($value instanceof \DateTimeInterface) {
                    return $value->format(\DateTimeInterface::ATOM);
                }
                return $value;
            })
            ->setAllowedTypes('birthCity', ['null', 'string'])
            ->setAllowedTypes('birthCountry', ['null', 'string']) // Country code (ISO-3166-Alpha2)
            ->setNormalizer('birthCountry', function (Options $options, $value) {
                return strtoupper($value);
            })
            ->setAllowedValues('birthCountry', function ($value) {
                return is_null($value) || preg_match('/^[a-zA-Z]{2}$/', $value);
            })
            ->setAllowedTypes('addressFirst', ['null', 'string'])
            ->setAllowedTypes('addressCity', ['null', 'string'])
            ->setAllowedTypes('addressPostal', ['null', 'string'])
            ->setAllowedTypes('addressCountry', ['null', 'string']) // Country code (ISO-3166-Alpha2)
            ->setNormalizer('addressCountry', function (Options $options, $value) {
                return strtoupper($value);
            })
            ->setAllowedValues('addressCountry', function ($value) {
                return is_null($value) || preg_match('/^[a-zA-Z]{2}$/', $value);
            })
            ->setAllowedTypes('identifierType', ['string'])
            ->setAllowedValues('identifierType', function ($value) {
                return in_array(
                    $value,
                    [
                        Seller::IDENTIFIER_SIREN,
                        Seller::IDENTIFIER_CIF,
                        Seller::IDENTIFIER_NIF,
                        Seller::IDENTIFIER_KVK,
                        Seller::IDENTIFIER_HR,
                        Seller::IDENTIFIER_CHRN,
                        Seller::IDENTIFIER_BERN,
                        Seller::IDENTIFIER_VAT,
                    ]
                );
            })
            ->setAllowedTypes('identifier', ['string', 'numeric'])
            ->setNormalizer('identifier', function (Options $options, $value) {
                if (Seller::IDENTIFIER_SIREN === $options['identifierType']) {
                    return is_string($value) ? str_replace(' ', '', $value) : $value;
                }

                return $value;
            })
            ->setAllowedTypes('iban', ['string'])
            ->setNormalizer('iban', function (Options $options, $value) {
                return strtoupper(str_replace(' ', '', $value));
            })
            ->setAllowedTypes('idFileFront', ['array'])
            ->setAllowedValues('idFileFront', function (&$value) {
                if (empty($value)) {
                    throw new InvalidOptionsException('Option "idFileFront" cannot be empty');
                }

                if (empty($value['document']) || empty($value['contentType'])) {
                    throw new InvalidOptionsException('Option "idFileFront" must be an array and have document and contentType keys (fileName is optional)');
                }

                if (!in_array($value['contentType'], self::ALLOWED_MIME_TYPES)) {
                    throw new InvalidOptionsException('Value "contentType" of option "idFileFront" invalid');
                }

                if (empty($value['fileName'])) {
                    $value['fileName'] = 'ownerIdFile';
                }

                return true;
            })
            ->setAllowedTypes('idFileBack', ['null', 'array'])
            ->setAllowedValues('idFileBack', function (&$value) {
                if (empty($value)) {
                    return true;
                }

                if (empty($value['document']) || empty($value['contentType'])) {
                    throw new InvalidOptionsException('Option "idFileBack" must be an array and have document and contentType keys (fileName is optional)');
                }

                if (!in_array($value['contentType'], self::ALLOWED_MIME_TYPES)) {
                    throw new InvalidOptionsException('Value "contentType" of option "idFileBack" invalid');
                }

                if (empty($value['fileName'])) {
                    $value['fileName'] = 'ownerIdVerso';
                }

                return true;
            })
            ->setAllowedTypes('termsAcceptationDate', [\DateTimeInterface::class])
            ->setNormalizer('termsAcceptationDate', function (Options $options, $value) {
                return $value->format(\DateTimeInterface::ATOM);
            })
        ;
    }
}
