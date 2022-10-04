<?php declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Shopware\AppBundle\Shop\ShopInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SignatureService
{
    public const SHOP_SIGNATURE = 'shopware-shop-signature';
    public const APP_SIGNATURE = 'shopware-app-signature';
    public const APP_SECRET = 'shopware-app-secret';

    public function __construct(
        private string $appSecret
    ) {
    }

    public function verifyRequestContent(Request $request, ShopInterface $shop): void
    {
        if (!\in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            throw new \RuntimeException('Request is not a post.');
        }
        if (!$request->headers->has(self::SHOP_SIGNATURE)) {
           throw new \RuntimeException('Missing request signature.');
        }
        $this->verifySignature(
            $shop->getShopSecret(),
            $request->getContent(),
            $request->headers->get(self::SHOP_SIGNATURE)
        );
    }

    public function verifyRequestQuery(Request $request, ShopInterface $shop): void
    {
        if (!$request->query->has(self::SHOP_SIGNATURE)) {
            throw new \RuntimeException('Missing request signature.');
        }

        $uri = AdminRequest::getQueryFromRequest($request);
        $uri = Uri::withoutQueryValue($uri, self::SHOP_SIGNATURE);
        $signature = $request->query->get(self::SHOP_SIGNATURE);

        $this->verifySignature(
            $shop->getShopSecret(),
            $uri->getQuery(),
            $signature
        );

        $request->attributes->set(self::APP_SIGNATURE, $uri);
        $request->attributes->set(self::APP_SECRET, $shop->getShopSecret());
    }

    public function signResponseWithSignature(Response $response, ?string $secret): void
    {
        $signature = hash_hmac(
            'sha256',
            $response->getContent(),
            $secret ?? $this->appSecret
        );
        $response->headers->set(self::APP_SIGNATURE, $signature);
    }

    public function signQueryWithSignature(UriInterface $queryUri, ?string $secret): UriInterface
    {
        $queryUri = Uri::withQueryValue($queryUri, 'timestamp', (string)time());
        $queryUri = Uri::withoutQueryValue($queryUri, self::SHOP_SIGNATURE);
        $signature = hash_hmac(
            'sha256',
            $queryUri->getQuery(),
            $secret ?? $this->appSecret
        );
        return Uri::withQueryValue($queryUri, self::SHOP_SIGNATURE, $signature);
    }

    public function createSignedResponse(array $data, string $secret = null): JsonResponse
    {
        $response = new JsonResponse($data);
        $this->signResponseWithSignature($response, $secret);
        return $response;
    }

    private function verifySignature(string $secret, string $message, string $signature): void
    {
        $hmac = hash_hmac('sha256', $message, $secret);
        if (!hash_equals($hmac, $signature)) {
            throw new \RuntimeException('Request signature is not valid.');
        }
    }
}
