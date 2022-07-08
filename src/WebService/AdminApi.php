<?php declare(strict_types=1);

namespace App\WebService;

use App\Shop\ShopEntity;
use App\Shop\ShopRepository;
use DateInterval;
use DateTime;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Utils;
use Psr\Http\Message\ResponseInterface;
use Shopware\AppBundle\Shop\ShopInterface;

class AdminApi
{
    public function __construct(
        private ClientInterface $client,
        private ShopRepository  $repository
    )
    {
    }

    public function getAccessToken(ShopEntity $shop): string
    {
        if ($shop->getAccessToken()) {
            $now = new DateTime();
            // For the next 30 seconds fresh
            $now->sub(new DateInterval('PT30S'));
            if ($shop->getTokenExpiresIn() > $now) {
                return $shop->getAccessToken();
            }
            if ($shop->getTokenExpiresIn() > new DateTime()) {
                $tokenData = $this->refreshAccessToken($shop);
            } else {
                $tokenData = $this->readAccessToken($shop);
            }
        } else {
            $tokenData = $this->readAccessToken($shop);
        }

        $expiresIn = new DateTime();
        $expiresIn->add(new DateInterval('PT' . $tokenData['expires_in'] . 'S'));
        $shop->setTokenExpiresIn($expiresIn);
        $shop->setAccessToken($tokenData['access_token']);

        $this->repository->saveAccessToken($shop);

        return $shop->getAccessToken();
    }

    protected function readAccessToken(ShopInterface $shop): array
    {
        $response = $this->client->request('POST',
            $shop->getUrl() . '/api/oauth/token', [
                'json' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $shop->getApiKey(),
                    'client_secret' => $shop->getSecretKey(),
                ],
            ]);

        return $this->getResult($response);
    }

    protected function refreshAccessToken(ShopEntity $shop): array
    {
        $response = $this->client->request('POST',
            $shop->getUrl() . '/api/oauth/token', [
                'json' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $shop->getApiKey(),
                    'refresh_token' => $shop->getAccessToken(),
                ],
            ]);

        return $this->getResult($response);
    }

    public function getProduct(ShopEntity $shop, string $productId, string $languageId): array
    {
        $response = $this->client->request('GET',
            $shop->getUrl() . '/api/product/' . $productId, [
                'headers' => $this->getRequestHeaders($shop, $languageId)
            ]);

        return $this->getResult($response);
    }

    public function updateProduct(ShopEntity $shop, string $productId, string $languageId): array
    {
        $response = $this->client->request('PUT',
            $shop->getUrl() . '/api/product/' . $productId, [
                'headers' => $this->getRequestHeaders($shop, $languageId)
            ]);

        return $this->getResult($response)['data'];
    }

    public function getEditorCustomFields(ShopEntity $shop, string $languageId): array
    {
        $response = $this->client->request('POST',
            $shop->getUrl() . '/api/search/custom-field', [
                'headers' => $this->getRequestHeaders($shop, $languageId),
                'json' => [
                    'filter' => [[
                        'type' => 'multi',
                        'operator' => 'and',
                        'queries' => [[
                            'type' => 'equals',
                            'field' => 'customFieldSet.relations.entityName',
                            'value' => 'product'
                        ], [
                            'type' => 'equals',
                            'field' => 'type',
                            'value' => 'html'
                        ]]
                    ]]
                ]
            ]
        );

        return $this->getResult($response)['data'];
    }

    private function getRequestHeaders(ShopEntity $shop, ?string $languageId): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getAccessToken($shop),
            'Accept' => 'application/json',
            'sw-language-id' => $languageId
        ];
    }

    private function getResult(ResponseInterface $response): array
    {
        return Utils::jsonDecode($response->getBody()->getContents(), true);
    }
}