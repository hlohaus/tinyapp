<?php declare(strict_types=1);

namespace App\Service;

use App\Shop\ShopEntity;
use App\Shop\ShopRepository;
use DateInterval;
use DateTime;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Utils;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface;
use Shopware\AppBundle\Shop\ShopInterface;
use Symfony\Component\HttpFoundation\Request;

class AdminApi
{
    public function __construct(
        private AdminClient $client
    ) {
    }

    public function getEntity(ShopEntity $shop, string $languageId, string $entity, string $entityId): array
    {
        $result = $this->client->request('GET',
            sprintf('api/%s/%s', $entity, $entityId),
            $this->client->getDefaultOptions($shop, $languageId),
        );
        if(!isset($result['data'])) {
            var_export($result);die;
        }
        return $result['data'];
    }

    public function updateEntity(ShopEntity $shop, string $languageId, string $entity, string $entityId, array $request): array
    {
        return $this->client->request('PATCH',
            sprintf('/api/%s/%s', $entity, $entityId),
            $this->client->getDefaultOptions($shop, $languageId),
            $request,
        );
    }

    public function list(ShopEntity $shop, string $languageId, string $entity): array
    {
        return $this->client->request('GET',
            sprintf('api/%s', $entity),
            $this->client->getDefaultOptions($shop, $languageId),
        );
    }

    public function search(ShopEntity $shop, string $languageId, string $entity, array $request): array
    {
        return $this->client->request('POST',
            sprintf('api/search/%s', $entity),
            $this->client->getDefaultOptions($shop, $languageId),
            $request,
        );
    }

    public function getEditorCustomFields(ShopEntity $shop, string $languageId, string $entity): array
    {
        return $this->client->request('POST',
            '/api/search/custom-field',
            $this->client->getDefaultOptions($shop, $languageId), [
                'filter' => [[
                    'type' => 'multi',
                    'operator' => 'and',
                    'queries' => [[
                        'type' => 'equals',
                        'field' => 'customFieldSet.relations.entityName',
                        'value' => $entity
                    ], [
                        'type' => 'equals',
                        'field' => 'type',
                        'value' => 'html'
                    ]]
                ]]
            ]
        )['data'];
    }
}