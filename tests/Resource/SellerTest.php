<?php

namespace Medelse\DimplBundle\Tests\Resource;

use Medelse\DimplBundle\Resource\Seller;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SellerTest extends TestCase
{
    public function testCreateSeller()
    {
        $sellerResource = $this->getSellerResource(json_encode(['sellerId' => '12345']));
        $response = $sellerResource->createSeller($this->getSellerData());

        $this->assertIsArray($response);
        $this->assertArrayHasKey('sellerId', $response);
        $this->assertEquals('12345', $response['sellerId']);
    }

    public function testCreateSellerReturnsError()
    {
        $sellerResource = $this->getSellerResource('invalid field \'identifier\' format',400);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Error 400 : invalid field \'identifier\' format ()');
        $sellerResource->createSeller($this->getSellerData());
    }

    /**
     *
     * PRIVATE
     *
     */

    private function getSellerData(): array
    {
        return [
            'phone' => '+33909090909',
            'email' => 'zombieland@dimpl.com',
            'givenName' => 'Bill',
            'familyName' => 'Murray',
            'nationality' => 'us',
            'birthDate' => new \DateTime('1990-01-01 00:00:00'),
            'birthCity' => 'Austin',
            'birthCountry' => 'us',
            'addressFirst' => 'Pacific Playland',
            'addressCity' => 'Austin',
            'addressPostal' => '05000',
            'addressCountry' => 'us',
            'identifierType' => 'siren',
            'identifier' => '123456789',
            'iban' => 'FR14 3000 1019 0100 00Z6 7067 032',
            'idFileFront' => 'zombies_front',
            'idFileBack' => 'zombies_back',
            'termsAcceptationDate' => new \DateTime('2022-01-01 10:00:00'),
        ];
    }

    private function getSellerResource(string $bodyResponse, int $responseCode = 200): Seller
    {
        $response = new MockResponse($bodyResponse,['http_code' => $responseCode]);
        $httpClient = new MockHttpClient($response, 'https://example.com');

        return new Seller(
            $httpClient,
            'https://factor-dev-marketplaces-api.azurewebsites.net/',
            'clientApiKey'
        );
    }
}
