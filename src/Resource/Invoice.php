<?php

namespace Medelse\DimplBundle\Resource;

use Medelse\DimplBundle\Resolver\Invoice\CreateInvoiceResolver;
use Symfony\Component\HttpFoundation\Request;

class Invoice extends Resource
{
    public const STATUS_PROCESSING = 'PROCESSING'; // We didn't check the advance yet.
    public const STATUS_ACCEPTED   = 'ACCEPTED'; // We accepted your advance but did not make the payment yet.
    public const STATUS_PENDING    = 'PENDING'; // We accepted the advance, made the payment, and are waiting for the repayment.
    public const STATUS_REFUSED    = 'REFUSED'; // We refused the advance because of the information filled or the end client solvability.
    public const STATUS_PAID       = 'PAID'; // We received your payment.
    public const STATUS_LATE       = 'LATE'; // Your payment is late.

    public const CREATE_INVOICE_URL = '/'.self::API_VERSION.'/invoices/add-and-finance';
    public const GET_INVOICE_URL    = '/'.self::API_VERSION.'/invoices/{invoiceId}';

    public function createInvoice(array $data): array
    {
        $createResolver = new CreateInvoiceResolver();

        return $this->addStatusToResponse(
            $this->sendRequestFormData(
                Request::METHOD_POST,
                self::CREATE_INVOICE_URL,
                $createResolver->resolve($data)
            )
        );
    }

    public function getInvoice(string $invoiceId): array
    {
        $path = str_replace(
            '{invoiceId}',
            $invoiceId,
            self::GET_INVOICE_URL
        );

        return $this->addStatusToResponse($this->sendGetRequest($path));
    }

    public function parseWebhookBody(string $body): array
    {
        $hookResponse = json_decode($body, true);

        if (false !== $hookResponse) {
            $hookResponse = $hookResponse[0];
            $hookResponse = $this->addStatusToResponse($hookResponse);

            if (isset($hookResponse['invoiceId'])) {
                $response = $this->getInvoice($hookResponse['invoiceId']);
            } else {
                $response = $hookResponse;
            }

            if (self::STATUS_REFUSED === $hookResponse['status']) {
                $response = array_merge(
                    $response,
                    ['status' => $hookResponse['status']]
                );
            }

            return $response;
        }

        return [];
    }

    private function addStatusToResponse(array $response): array
    {
        return array_merge(
            $response,
            ['status' => $this->calculateStatus($response)]
        );
    }

    private function calculateStatus(array $response): string
    {
        // Field set on response when creating Invoice if invoice not eligible
        if (isset($response['notEligibleReason'])) {
            return self::STATUS_REFUSED;
        }

        // Field set on response when creating Invoice if invoice eligible but other problem like user not certified
        if (isset($response['eligibleCannotFinanceReason'])) {
            return self::STATUS_PROCESSING;
        }

        // Field set on webhook when invoice accepted or rejected by dimpl
        if (isset($response['status'])) {
            if ('Accepted' === $response['status']) {
                return self::STATUS_ACCEPTED;
            }

            if ('Rejected' === $response['status']) {
                return self::STATUS_REFUSED;
            }
        }

        // other fields bellow are set when getting invoice
        if (isset($response['completelyPaidDate'])) {
            return self::STATUS_PAID;
        }

        if (isset($response['dueDate'])) {
            $dueDate = new \DateTime($response['dueDate']);

            if ($dueDate <= new \DateTime()) {
                return self::STATUS_LATE;
            }
        }

        if (isset($response['amountLeftToPayCents']) and $response['amountLeftToPayCents'] > 0) {
            return self::STATUS_PENDING;
        }

        return self::STATUS_PROCESSING;
    }
}
