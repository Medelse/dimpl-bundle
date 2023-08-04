<?php

namespace Medelse\DimplBundle\Resource;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class Resource
{
    public const API_VERSION = 'v2';
    public const API_KEY_HEADER = 'DimplApiKey';

    protected HttpClientInterface $httpClient;
    protected string $dimplBaseUrl;
    protected string $dimplApiKey;

    public function __construct(HttpClientInterface $httpClient, string $dimplBaseUrl, string $dimplApiKey)
    {
        $this->httpClient = $httpClient;
        $this->dimplBaseUrl = $dimplBaseUrl;
        $this->dimplApiKey = $dimplApiKey;
    }

    protected function sendGetRequest(string $path): array
    {
        $response = $this->httpClient->request(
            Request::METHOD_GET,
            $this->dimplBaseUrl . $path,
            [
                'headers' => [
                    self::API_KEY_HEADER . ':' . $this->dimplApiKey,
                ],
            ]
        );

        return $this->processResponse($response);
    }

    protected function sendPostOrPatchOrPutRequest(string $method, string $path, array $body = []): array
    {
        $allowedMethods = [Request::METHOD_POST, Request::METHOD_PATCH, Request::METHOD_PUT];
        if (!in_array($method, $allowedMethods)) {
            throw new \InvalidArgumentException(sprintf('Allowed http methods for function sendPostOrPatchRequest are %s', implode(', ', $allowedMethods)));
        }

        $response = $this->httpClient->request(
            $method,
            $this->dimplBaseUrl . $path,
            [
                'json' => $body,
                'headers' => [
                    self::API_KEY_HEADER . ':' . $this->dimplApiKey,
                ],
            ]
        );

        return $this->processResponse($response);
    }

    protected function sendRequestFormData(string $method, string $path, array $body): array
    {
        $allowedMethods = [Request::METHOD_POST, Request::METHOD_PUT];
        if (!in_array($method, $allowedMethods)) {
            throw new \InvalidArgumentException(sprintf('Allowed http methods for function sendRequestFormData are %s', implode(', ', $allowedMethods)));
        }

        $formData = new FormDataPart($body);

        $response = $this->httpClient->request(
            $method,
            $this->dimplBaseUrl . $path,
            [
                'headers' => array_merge(
                    $formData->getPreparedHeaders()->toArray(),
                    [
                        self::API_KEY_HEADER . ':' . $this->dimplApiKey,
                    ]
                ),
                'body' => $formData->bodyToString(),
            ]
        );

        return $this->processResponse($response);
    }

    protected function processResponse(ResponseInterface $response): array
    {
        if (strpos((string)$response->getStatusCode(), '2') !== 0) {
            $status  = $response->getStatusCode();
            try {
                $data = $response->toArray(false);
                $message = $data['title'] ?? '';
            } catch (\JsonException $e) {
                $data = [];
                $message = $response->getContent(false);
            }
            $errors = [];

            if (isset($data['errors'])) {
                foreach ($data['errors'] as $field => $errorMessage) {
                    $errors[] = $field . ' => ' . $errorMessage;
                }
            }

            throw new BadRequestException(
                sprintf(
                    'Error %s : %s (%s)',
                    $status,
                    $message,
                    implode(' | ', $errors)
                )
            );
        }

        try {
            $data = $response->toArray(false);
        } catch (\JsonException $e) {
            $data = [];
        }

        return $data;
    }
}
