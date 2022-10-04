<?php declare(strict_types=1);

namespace App\Service;

use App\Shop\ShopEntity;
use App\Shop\ShopRepository;
use DateTime;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Utils;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface;

class AdminClient
{
    private const CONTENT_TYPE = 'application/json';

    public function __construct(
        private ClientInterface $client,
        private ShopRepository  $repository
    ) {
    }

    public function request(string $method, string $url, array $options = [], array $post = null): array
    {
        $options['headers']['Accept'] = self::CONTENT_TYPE;
        if (isset($post)) {
            $options['headers']['Content-Type'] = self::CONTENT_TYPE;
            $options['json'] = $post;
        }
        try {
            return self::decodeResponse(
                $this->client->request($method, $url, $options)
            );
        } catch (RequestException $e) {
            if ($e->getCode() === 401) {
            }
            return self::decodeResponse($e->getResponse());
        }
    }

    #[ArrayShape(['base_uri' => 'string', 'headers' => 'string[]'])]
    public function getDefaultOptions(ShopEntity $shop, string $languageId = null): array
    {
        return [
            'base_uri' => $shop->getUrl(),
            'headers' => $this->createAuthHeaders($shop, $languageId)
        ];
    }

    protected function getAccessToken(ShopEntity $shop): string
    {
        if ($shop->isAccessTokenFresh()) {
            return $shop->getAccessToken();
        }

        return $this->refreshToken($shop);
    }

    protected function refreshToken(ShopEntity $shop): string
    {
        $tokenData = $this->requestAccessToken($shop);

        $shop->setAccessToken($tokenData['access_token']);
        $shop->setTokenExpiresInFromSeconds($tokenData['expires_in']);

        $this->repository->saveAccessToken($shop);

        return $shop->getAccessToken();
    }

    #[ArrayShape(['access_token' => 'string', 'expires_in' => 'int'])]
    protected function requestAccessToken(ShopEntity $shop): array
    {
        $request = match ($shop->getTokenExpiresIn() > new DateTime()) {
            true => [
                'grant_type' => 'refresh_token',
                'client_id' =>  $shop->getApiKey(),
                'client_secret' => $shop->getAccessToken(),
            ],
            default => [
                'grant_type' => 'client_credentials',
                'client_id' =>  $shop->getApiKey(),
                'client_secret' => $shop->getSecretKey(),
            ],
        };
        return $this->request(
            'POST',
            $shop->getUrl() . '/api/oauth/token',
            [],
            $request
        );
    }

    #[ArrayShape(['Authorization' => 'string', 'sw-language-id' => 'null|string'])]
    protected function createAuthHeaders(ShopEntity $shop, ?string $languageId): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken($shop),
        ];
        if (isset($languageId)) {
            $headers['sw-language-id'] = $languageId;
        }
        return $headers;
    }

    private static function decodeResponse(ResponseInterface $response): array
    {
        if ($response->getStatusCode() === 204) {
            return [];
        }
        return Utils::jsonDecode($response->getBody()->getContents(), true);
    }
}