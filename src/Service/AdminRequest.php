<?php declare(strict_types=1);

namespace App\Service;

use App\Shop\ShopEntity;
use App\Shop\ShopRepository;
use DateInterval;
use DateTime;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Utils;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Shopware\AppBundle\Shop\ShopInterface;
use Symfony\Component\HttpFoundation\Request;

class AdminRequest
{
    public function __construct(
        private ShopRepository $repository,
        private AdminApi $api,
        private SignatureService $signatureService
    ) {
    }

    public function getShopFromRequest(Request $request): ?ShopEntity
    {
        $shopId = $request->query->getAlnum('shop-id');
        $shop = $this->repository->getShopFromId($shopId);
        $this->signatureService->verifyRequestQuery($request, $shop);
        $shop->setProxyUrl('http://swag:8000');

        return $shop;
    }

    public function updateEntityFromRequest(Request $request): void
    {
        $this->api->updateEntity(
            $this->getShopFromRequest($request),
            $request->query->get('sw-context-language'),
            $request->query->get('entity'),
            $request->query->get('entityId'),
            $request->request->all()
        );
    }

    #[ArrayShape(['entity' => 'string', 'entityId' => 'string', 'appSecret' => 'string'])]
    public function getResourceFromRequest(Request $request): array
    {
        $requestContent = json_decode($request->getContent(), true);
        $shopId = $requestContent['source']['shopId'] ?? $request->query->get('shop-id');
        $shop = $this->repository->getShopFromId($shopId);
        $this->signatureService->verifyRequestContent($request, $shop);

        return [
            'entity' => $requestContent['data']['entity'] ?? null,
            'entityId' => $requestContent['data']['ids'][0] ?? null,
            'appSecret' => $shop->getShopSecret()
        ];
    }

    public static function getQueryFromRequest(Request $request): UriInterface
    {
        $uri = new Uri($request->getUriForPath($request->getPathInfo()));
        $uri = $uri->withQuery($request->server->get('QUERY_STRING'));
        //$uri = $uri->withQueryValues($uri, $request->query->all());
        return $uri->withoutQueryValue($uri, 'q');
    }

    public static function withQueryArray(UriInterface $uri, string $key, array $options): UriInterface
    {
        foreach ($options as $option => $value) {
            $name = $key . '[' . $option . ']';
            $options[rawurlencode($name)] = $value;
        }
        return Uri::withQueryValues($uri, $options);
    }
}