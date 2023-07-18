<?php

namespace Medelse\DimplBundle\Tests\Resource;

use Medelse\DimplBundle\Resource\Invoice;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class InvoiceTest extends TestCase
{
    public function testCreateInvoiceSuccess()
    {
        $invoiceResource = $this->getInvoiceResource(json_encode(['invoiceId' => '12345']));
        $response = $invoiceResource->createInvoice($this->getInvoiceData());

        $this->assertIsArray($response);
        $this->assertArrayHasKey('invoiceId', $response);
        $this->assertEquals('12345', $response['invoiceId']);
        $this->assertEquals('PROCESSING', $response['status']);
    }

    public function testCreateInvoiceRefusedSuccess()
    {
        $invoiceResource = $this->getInvoiceResource(
            json_encode(
                [
                    'invoiceId' => '12345',
                    'notEligibleReason' => 'Client non éligible'
                ]
            )
        );
        $response = $invoiceResource->createInvoice($this->getInvoiceData());

        $this->assertIsArray($response);
        $this->assertArrayHasKey('invoiceId', $response);
        $this->assertEquals('12345', $response['invoiceId']);
        $this->assertEquals('Client non éligible', $response['notEligibleReason']);
        $this->assertEquals('REFUSED', $response['status']);
    }

    public function testCreateInvoiceCannotFinanceSuccess()
    {
        $invoiceResource = $this->getInvoiceResource(
            json_encode(
                [
                    'invoiceId' => '12345',
                    'eligibleCannotFinanceReason' => 'Your profile is under certification',
                ]
            )
        );
        $response = $invoiceResource->createInvoice($this->getInvoiceData());

        $this->assertIsArray($response);
        $this->assertArrayHasKey('invoiceId', $response);
        $this->assertEquals('12345', $response['invoiceId']);
        $this->assertEquals('Your profile is under certification', $response['eligibleCannotFinanceReason']);
        $this->assertEquals('PROCESSING', $response['status']);
    }

    public function testCreateInvoiceReturnsError()
    {
        $invoiceResource = $this->getInvoiceResource(
            json_encode(
                [
                    'status' => 400,
                    'title' => 'One or more validation errors occurred.',
                    'errors' => [
                        'sellerId' => "The value '123-id' is not valid.",
                    ],
                ]
            ),
            400
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Error 400 : One or more validation errors occurred. (sellerId => The value \'123-id\' is not valid.)');
        $invoiceResource->createInvoice($this->getInvoiceData());
    }

    public function testCreateInvoiceReturnsErrors()
    {
        $invoiceResource = $this->getInvoiceResource(
            json_encode(
                [
                    'status' => 400,
                    'title' => 'One or more validation errors occurred.',
                    'errors' => [
                        'sellerId' => 'The value \'123-id\' is not valid.',
                        'buyerIdentifier' => 'The value \'123456789\' is not valid.',
                    ],
                ]
            ),
            400
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Error 400 : One or more validation errors occurred. (sellerId => The value \'123-id\' is not valid. | buyerIdentifier => The value \'123456789\' is not valid.)');
        $invoiceResource->createInvoice($this->getInvoiceData());
    }

    public function testCreateInvoiceAlreadyExistsReturnsError()
    {
        $invoiceResource = $this->getInvoiceResource('Facture existe déjà : \'123\'', 400);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Error 400 : Facture existe déjà : \'123\' ()');
        $invoiceResource->createInvoice($this->getInvoiceData());
    }

    public function testGetInvoiceReturnsSuccess()
    {
        $invoiceResource = $this->getInvoiceResource(json_encode($this->getInvoiceResponse()));
        $response = $invoiceResource->getInvoice('123');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('123', $response['id']);
        $this->assertEquals(null, $response['completelyPaidDate']);
        $this->assertEquals(50000, $response['amountWithoutTaxesCents']);
    }

    public function testGetInvoiceStatusTests()
    {
        $response = $this->getInvoiceResponse();
        $invoiceResource = $this->getInvoiceResource(json_encode($response));
        $response = $invoiceResource->getInvoice('123');

        $this->assertEquals('PENDING', $response['status']);

        //LATE
        $response['dueDate'] = (new \DateTime('-1 week'))->format('Y-m-d');
        $invoiceResource = $this->getInvoiceResource(json_encode($response));
        $response = $invoiceResource->getInvoice('123');

        $this->assertEquals('LATE', $response['status']);

        //PAID
        $response['completelyPaidDate'] = '2023-001';
        $invoiceResource = $this->getInvoiceResource(json_encode($response));
        $response = $invoiceResource->getInvoice('123');

        $this->assertEquals('PAID', $response['status']);
    }

    public function testGetInvoiceReturnsError()
    {
        $invoiceResource = $this->getInvoiceResource('this invoice does not exist', 404);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Error 404 : this invoice does not exist ()');
        $invoiceResource->getInvoice('123');
    }

    public function testGetHookStatusAccepted()
    {
        $invoiceResource = $this->getInvoiceResource(json_encode($this->getInvoiceResponse()));

        $response = $invoiceResource->parseWebhookBody('[{"invoiceId": "123", "status": "Accepted"}]');
        $this->assertEquals('PENDING', $response['status']);
    }

    public function testGetHookStatusRefused()
    {
        $invoiceResource = $this->getInvoiceResource(json_encode($this->getInvoiceResponse()));

        $response = $invoiceResource->parseWebhookBody('[{"invoiceId": "123", "status": "Rejected"}]');
        $this->assertEquals('REFUSED', $response['status']);
    }

    /**
     *
     * PRIVATE
     *
     */

    private function getInvoiceData(): array
    {
        return [
            'sellerId' => '123-id',
            'identifierType' => 'siren',
            'identifier' => '123456789',
            'email' => 'zombieland@dimpl.com',
            'phone' => '+33909090909',
            'invoiceNumber' => '0012345',
            'issueDate' => new \DateTime('2023-01-01 10:00:00'),
            'dueDate' => new \DateTime('2023-03-01 18:00:00'),
            'amountWithoutTaxes' => 10000,
            'amountOfTaxes' => 0,
            'file' => 'zombies_invoice',
            'additionalFiles' => ['zombies_invoice_extra_1', 'zombies_invoice_extra_2'],
            'deliveryValidationDateTime' => new \DateTime('2023-01-01 09:00:00'),
        ];
    }

    private function getInvoiceResponse(): array
    {
        return [
            'id' => '123',
            'sellerId' => '123-id',
            'buyerIdentifier' => [
                'type' => 'SIREN',
                'value' => '123456',
            ],
            'creationDate' => '2023-01-01T00:00:00.000000',
            'number' => '123',
            'issueDate' => '2023-01-01',
            'dueDate' => (new \DateTime('+1 week'))->format('Y-m-d'),
            'amountWithoutTaxesCents' => 50000,
            'amountOfTaxesCents' => 0,
            'completelyPaidDate' => null,
            'amountLeftToPayCents' => 50000,
        ];
    }

    private function getInvoiceResource(string $bodyResponse, int $responseCode = 200): Invoice
    {
        $response = new MockResponse($bodyResponse,['http_code' => $responseCode]);
        $httpClient = new MockHttpClient($response, 'https://example.com');

        return new Invoice(
            $httpClient,
            'https://factor-dev-marketplaces-api.azurewebsites.net/',
            'clientApiKey'
        );
    }
}
