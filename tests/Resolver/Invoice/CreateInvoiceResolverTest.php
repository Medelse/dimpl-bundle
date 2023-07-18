<?php

namespace Medelse\DimplBundle\Tests\Resolver\Invoice;

use Medelse\DimplBundle\Resolver\Invoice\CreateInvoiceResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class CreateInvoiceResolverTest extends TestCase
{
    public function testResolve()
    {
        $invoice = $this->getInvoice();

        $resolver = new CreateInvoiceResolver();
        $data = $resolver->resolve($invoice);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('sellerId', $data);
        $this->assertEquals('123-id', $data['sellerId']);
        $this->assertArrayHasKey('buyerIdentifierType', $data);
        $this->assertEquals('siren', $data['buyerIdentifierType']);
        $this->assertArrayHasKey('buyerIdentifier', $data);
        $this->assertEquals('123456789', $data['buyerIdentifier']);
        $this->assertArrayHasKey('buyerEmail', $data);
        $this->assertEquals('zombieland@dimpl.com', $data['buyerEmail']);
        $this->assertArrayHasKey('buyerPhone', $data);
        $this->assertEquals('+33909090909', $data['buyerPhone']);
        $this->assertArrayHasKey('invoiceNumber', $data);
        $this->assertEquals('0012345', $data['invoiceNumber']);
        $this->assertArrayHasKey('invoiceIssueDate', $data);
        $this->assertStringStartsWith('2023-01-01T10:00:00', $data['invoiceIssueDate']);
        $this->assertArrayHasKey('invoiceDueDate', $data);
        $this->assertStringStartsWith('2023-03-01T18:00:00', $data['invoiceDueDate']);
        $this->assertArrayHasKey('invoiceAmountWithoutTaxesCents', $data);
        $this->assertEquals(10000, $data['invoiceAmountWithoutTaxesCents']);
        $this->assertArrayHasKey('invoiceAmountOfTaxesCents', $data);
        $this->assertEquals(0, $data['invoiceAmountOfTaxesCents']);
        $this->assertArrayHasKey('invoiceFile', $data);
        $this->assertTrue($data['invoiceFile'] instanceof DataPart);
        $this->assertArrayHasKey(0, $data);
        $this->assertTrue($data[0]['additionalFiles'] instanceof DataPart);
        $this->assertArrayHasKey(1, $data);
        $this->assertTrue($data[1]['additionalFiles'] instanceof DataPart);
        $this->assertArrayHasKey('deliveryValidationDateTime', $data);
        $this->assertStringStartsWith('2023-01-01T09:00:00', $data['deliveryValidationDateTime']);
    }

    /**
     *
     * PRIVATE
     *
     */

    private function getInvoice(): array
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
}
