<?php

namespace Medelse\DimplBundle\Tests\Resolver\Buyer;

use Medelse\DimplBundle\Resolver\Buyer\GetCreditLimitResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class GetCreditLimitResolverTest extends TestCase
{
    public function testResolve()
    {
        $data = [
            'identifierType' => 'siren',
            'identifier' => '123',
        ];

        $resolver = new GetCreditLimitResolver();
        $data = $resolver->resolve($data);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('identifierType', $data);
        $this->assertEquals('siren', $data['identifierType']);
        $this->assertArrayHasKey('identifier', $data);
        $this->assertEquals('123', $data['identifier']);
    }

    public function testInvalidIdentifierType()
    {
        $data = [
            'identifierType' => 'foo',
            'identifier' => '123',
        ];

        $resolver = new GetCreditLimitResolver();
        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve($data);
    }

    public function testMissingIdentifierType()
    {
        $data = [
            'identifier' => '123',
        ];

        $resolver = new GetCreditLimitResolver();
        $this->expectException(MissingOptionsException::class);
        $resolver->resolve($data);
    }

    public function testInvalidIdentifier()
    {
        $data = [
            'identifierType' => 'siren',
            'identifier' => [123],
        ];

        $resolver = new GetCreditLimitResolver();
        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve($data);
    }

    public function testMissingIdentifier()
    {
        $data = [
            'identifierType' => 'siren',
        ];

        $resolver = new GetCreditLimitResolver();
        $this->expectException(MissingOptionsException::class);
        $resolver->resolve($data);
    }
}
