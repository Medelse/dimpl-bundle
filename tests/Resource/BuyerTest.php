<?php

namespace Medelse\DimplBundle\Tests\Resource;

use Medelse\DimplBundle\Resource\Buyer;
use Medelse\DimplBundle\Resource\Resource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BuyerTest extends TestCase
{
    public const API_URL = 'https://foo.bar';
    public const API_KEY = 'buzz';

    public function testGetCreditLimitFromSiren()
    {
        $expectedCreditLimit = 300;
        $expectedURL = self::API_URL . '/' . Resource::API_VERSION . '/eligibility/buyer';
        $siren = '987654321';
        $mockedResponse = new MockResponse('{"creditLimit": '.$expectedCreditLimit.'}',['http_code' => 200]);
        $httpClient = new MockHttpClient($mockedResponse, 'https://example.com');
        $buyer = $this->setupBuyer($httpClient);

        $creditLimit = $buyer->getCreditLimit($siren);

        $this->assertSame(1, $httpClient->getRequestsCount());
        $this->assertSame('GET', $mockedResponse->getRequestMethod());
        $this->assertContains(
            'DimplApiKey: buzz',
            $mockedResponse->getRequestOptions()['headers'],
        );
        $this->assertCount(2, $mockedResponse->getRequestOptions()['query']);
        $this->assertStringContainsString(
            Buyer::IDENTIFIER_SIREN,
            $mockedResponse->getRequestOptions()['query']['identifierType'],
        );
        $this->assertStringContainsString(
            $siren,
            $mockedResponse->getRequestOptions()['query']['identifier'],
        );
        $this->assertStringStartsWith($expectedURL,$mockedResponse->getRequestUrl());
        $this->assertSame($expectedCreditLimit, $creditLimit);
    }

    public function testGetCreditLimitFromSirenError()
    {
        $mockedResponse = new MockResponse('foo',['http_code' => 400]);
        $httpClient = new MockHttpClient($mockedResponse, 'https://example.com');
        $buyer = $this->setupBuyer($httpClient);
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Error 400 : foo');

        $buyer->getCreditLimit('987654321');
    }

    private function setupBuyer(HttpClientInterface $httpClient): Buyer
    {
        return new Buyer($httpClient, self::API_URL, self::API_KEY);
    }
}
