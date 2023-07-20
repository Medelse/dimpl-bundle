<?php

namespace Medelse\DimplBundle\Tests\Resolver\Seller;

use Medelse\DimplBundle\Resolver\Seller\CreateSellerResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class CreateSellerResolverTest extends TestCase
{
    public function testResolve()
    {
        $user = $this->getUser();

        $resolver = new CreateSellerResolver();
        $data = $resolver->resolve($user);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('ownerMobilePhone', $data);
        $this->assertEquals('+33909090909', $data['ownerMobilePhone']);
        $this->assertArrayHasKey('ownerEmail', $data);
        $this->assertEquals('zombieland@dimpl.com', $data['ownerEmail']);
        $this->assertArrayHasKey('ownerFirstName', $data);
        $this->assertEquals('Bill', $data['ownerFirstName']);
        $this->assertArrayHasKey('ownerLastName', $data);
        $this->assertEquals('Murray', $data['ownerLastName']);
        $this->assertArrayHasKey('ownerNationality', $data);
        $this->assertEquals('us', $data['ownerNationality']);
        $this->assertArrayHasKey('ownerBirthDate', $data);
        $this->assertStringStartsWith('1990-01-01T00:00:00', $data['ownerBirthDate']);
        $this->assertArrayHasKey('ownerBirthCity', $data);
        $this->assertEquals('Austin', $data['ownerBirthCity']);
        $this->assertArrayHasKey('ownerBirthCountry', $data);
        $this->assertEquals('US', $data['ownerBirthCountry']);
        $this->assertArrayHasKey('ownerHomeAddress', $data);
        $this->assertEquals('Pacific Playland', $data['ownerHomeAddress']);
        $this->assertArrayHasKey('ownerHomeCity', $data);
        $this->assertEquals('Austin', $data['ownerHomeCity']);
        $this->assertArrayHasKey('ownerHomePostCode', $data);
        $this->assertEquals('05000', $data['ownerHomePostCode']);
        $this->assertArrayHasKey('ownerHomeCountry', $data);
        $this->assertEquals('US', $data['ownerHomeCountry']);
        $this->assertArrayHasKey('identifierType', $data);
        $this->assertEquals('siren', $data['identifierType']);
        $this->assertArrayHasKey('identifier', $data);
        $this->assertEquals('123456789', $data['identifier']);
        $this->assertArrayHasKey('iban', $data);
        $this->assertEquals('FR1430001019010000Z67067032', $data['iban']);
        $this->assertArrayHasKey('ownerIdFile', $data);
        $this->assertTrue($data['ownerIdFile'] instanceof DataPart);
        $this->assertArrayHasKey('ownerIdVerso', $data);
        $this->assertTrue($data['ownerIdVerso'] instanceof DataPart);
        $this->assertArrayHasKey('dimplTermsAcceptationDateTime', $data);
        $this->assertStringStartsWith('2022-01-01T10:00:00', $data['dimplTermsAcceptationDateTime']);
    }

    public function testBadIdentifierTypeValue()
    {
        $user = $this->getUser();
        $user['identifierType'] = 'Little Rock';

        $resolver = new CreateSellerResolver();
        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve($user);
    }

    public function testSirenValueNormalizer()
    {
        $user = $this->getUser();
        $user['identifier'] = '123 456 789';

        $resolver = new CreateSellerResolver();
        $data = $resolver->resolve($user);

        $this->assertIsArray($data);
        $this->assertEquals('123456789', $data['identifier']);
    }


    public function testBankAccountIBANWithLowerLettersAndSpaces()
    {
        $user = $this->getUser();
        $user['iban'] = 'fr14 3000 1019 0100 00z6 7067 032';

        $resolver = new CreateSellerResolver();
        $data = $resolver->resolve($user);

        $this->assertIsArray($data);
        $this->assertEquals('FR1430001019010000Z67067032', $data['iban']);
    }

    public function testBadEmailValue()
    {
        $user = $this->getUser();
        $user['email'] = 'this_is_not_a_valid_email';

        $resolver = new CreateSellerResolver();
        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve($user);
    }

    public function testEmptyIdFileBack()
    {
        $user = $this->getUser();
        $user['idFileBack'] = null;

        $resolver = new CreateSellerResolver();
        $data = $resolver->resolve($user);
        $this->assertArrayNotHasKey('ownerIdVerso', $data);
    }

    /**
     *
     * PRIVATE
     *
     */

    private function getUser(): array
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
            'idFileFront' => [
                'document' => 'zombies_front.pdf',
                'contentType' => 'application/pdf',
            ],
            'idFileBack' => [
                'document' => 'zombies_back.pdf',
                'contentType' => 'application/pdf',
            ],
            'termsAcceptationDate' => new \DateTime('2022-01-01 10:00:00'),
        ];
    }
}
