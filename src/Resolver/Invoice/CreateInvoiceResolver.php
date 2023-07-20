<?php

namespace Medelse\DimplBundle\Resolver\Invoice;

use Medelse\DimplBundle\Resource\Seller;
use Medelse\DimplBundle\Tool\ArrayFormatter;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateInvoiceResolver
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

        $extraData = [];
        if (!empty($data['additionalFiles'])) {
            foreach ($data['additionalFiles'] as $invoiceAdditionalFile) {
                $extraData[] = [
                    'additionalFiles' => new DataPart(
                        $invoiceAdditionalFile['document'],
                        $invoiceAdditionalFile['fileName'],
                        $invoiceAdditionalFile['contentType']
                    )
                ];
            }
        }

        return ArrayFormatter::removeNullValues(
            array_merge(
                [
                    'sellerId' => $data['sellerId'],
                    'buyerIdentifierType' => $data['identifierType'],
                    'buyerIdentifier' => $data['identifier'],
                    'buyerEmail' => $data['email'],
                    'buyerPhone' => $data['phone'],
                    'invoiceNumber' => $data['invoiceNumber'],
                    'invoiceIssueDate' => $data['issueDate'],
                    'invoiceDueDate' => $data['dueDate'],
                    'invoiceAmountWithoutTaxesCents' => $data['amountWithoutTaxes'],
                    'invoiceAmountOfTaxesCents' => $data['amountOfTaxes'],
                    'invoiceFile' => new DataPart(
                        $data['file']['document'],
                        $data['file']['fileName'],
                        $data['file']['contentType']
                    ),
                    'deliveryValidationDateTime' => $data['deliveryValidationDateTime'],
                ],
                $extraData
            )
        );
    }

    private function configureOptionsResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefined([
            'sellerId',
            'identifierType',
            'identifier',
            'email',
            'phone',
            'invoiceNumber',
            'issueDate',
            'dueDate',
            'amountWithoutTaxes',
            'amountOfTaxes',
            'file',
            'additionalFiles',
            'deliveryValidationDateTime',
        ]);

        $resolver->setRequired([
            'sellerId',
            'identifierType',
            'identifier',
            'invoiceNumber',
            'issueDate',
            'dueDate',
            'amountWithoutTaxes',
            'amountOfTaxes',
            'file',
            'deliveryValidationDateTime',
        ]);

        $resolver
            ->setAllowedTypes('sellerId', ['string'])
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
            ->setAllowedTypes('email', ['null', 'string'])
            ->setAllowedValues('email', function ($value) {
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            })
            ->setAllowedTypes('phone', ['null', 'string'])
            ->setAllowedTypes('invoiceNumber', ['string', 'numeric'])
            ->setNormalizer('invoiceNumber', function (Options $options, $value) {
                return (string) $value;
            })
            ->setAllowedTypes('issueDate', [\DateTimeInterface::class])
            ->setNormalizer('issueDate', function (Options $options, $value) {
                return $value->format(\DateTimeInterface::ATOM);
            })
            ->setAllowedTypes('dueDate', [\DateTimeInterface::class])
            ->setNormalizer('dueDate', function (Options $options, $value) {
                return $value->format(\DateTimeInterface::ATOM);
            })
            ->setAllowedTypes('amountWithoutTaxes', ['int'])
            ->setAllowedValues('amountWithoutTaxes', function ($value) {
                return $value > 0;
            })
            ->setNormalizer('amountWithoutTaxes', function (Options $options, $value) {
                return (string) $value;
            })
            ->setAllowedTypes('amountOfTaxes', ['int'])
            ->setAllowedValues('amountOfTaxes', function ($value) {
                return $value >= 0;
            })
            ->setNormalizer('amountOfTaxes', function (Options $options, $value) {
                return (string) $value;
            })
            ->setAllowedTypes('file', ['array'])
            ->setAllowedValues('file', function (&$value) {
                if (empty($value)) {
                    return true;
                }

                if (empty($value['document']) || empty($value['contentType'])) {
                    throw new InvalidOptionsException('Option "file" must be an array and have document and contentType keys (fileName is optional)');
                }

                if (!in_array($value['contentType'], self::ALLOWED_MIME_TYPES)) {
                    throw new InvalidOptionsException('Value "contentType" of option "file" invalid');
                }

                if (empty($value['fileName'])) {
                    $value['fileName'] = 'file';
                }

                return true;
            })
            ->setAllowedTypes('additionalFiles', ['null', 'array'])
            ->setAllowedValues('additionalFiles', function (&$value) {
                foreach ($value as $key => &$invoiceAdditionalFile) {
                    if (empty($invoiceAdditionalFile['document']) || empty($invoiceAdditionalFile['contentType'])) {
                        throw new InvalidOptionsException('Option "additionalFiles" must be an array and each element have document and contentType keys (fileName is optional)');
                    }

                    if (!in_array($invoiceAdditionalFile['contentType'], self::ALLOWED_MIME_TYPES)) {
                        throw new InvalidOptionsException('Value "contentType" of option "additionalFiles" invalid');
                    }

                    if (empty($invoiceAdditionalFile['fileName'])) {
                        $invoiceAdditionalFile['fileName'] = 'additionalFiles_'.$key;
                    }
                }

                return true;
            })
            ->setAllowedTypes('deliveryValidationDateTime', [\DateTimeInterface::class])
            ->setNormalizer('deliveryValidationDateTime', function (Options $options, $value) {
                return $value->format(\DateTimeInterface::ATOM);
            })
        ;
    }
}
