<?php declare(strict_types=1);

namespace App\Shop;

use Doctrine\DBAL\Connection;
use Shopware\AppBundle\Shop\ShopInterface;
use Shopware\AppBundle\Shop\ShopRepositoryInterface;

class ShopRepository implements ShopRepositoryInterface
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function createShop(ShopInterface $shop): void
    {
        $this->deleteShop($shop);

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->insert('shop')
            ->setValue('shop_id', ':shop_id')
            ->setValue('shop_url', ':shop_url')
            ->setValue('shop_secret', ':shop_secret')
            ->setValue('api_key', ':api_key')
            ->setValue('secret_key', ':secret_key')
            ->setParameter('shop_id', $shop->getId())
            ->setParameter('shop_url', $shop->getUrl())
            ->setParameter('shop_secret', $shop->getShopSecret())
            ->setParameter('api_key', $shop->getApiKey())
            ->setParameter('secret_key', $shop->getSecretKey());

        $queryBuilder->execute();
    }

    public function getShopFromId(string $shopId): ShopInterface
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('shop')
            ->where('shop_id = :shop_id')
            ->setParameter('shop_id', $shopId);

        $shop = $queryBuilder->execute()->fetchAssociative();

        $shop['shop_url'] = str_replace('localhost', 'freudenberg', $shop['shop_url']);

        $result = new ShopEntity(
            $shop['shop_id'],
            $shop['shop_url'],
            $shop['shop_secret'],
            $shop['api_key'],
            $shop['secret_key']
        );

        if (isset($shop['access_token'])) {
            $result->setAccessToken($shop['access_token']);
            $result->setTokenExpiresIn(new \DateTime($shop['token_expires_in']));
        }

        return $result;
    }

    public function updateShop(ShopInterface $shop): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->update('shop')
            ->set('shop_url', ':shop_url')
            ->set('shop_secret', ':shop_secret')
            ->set('api_key', ':api_key')
            ->set('secret_key', ':secret_key')
            ->where('shop_id = :shop_id')
            ->setParameter('shop_id', $shop->getId())
            ->setParameter('shop_url', $shop->getUrl())
            ->setParameter('shop_secret', $shop->getShopSecret())
            ->setParameter('api_key', $shop->getApiKey())
            ->setParameter('secret_key', $shop->getSecretKey());

        $queryBuilder->execute();
    }

    public function deleteShop(ShopInterface $shop): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->delete('shop')
            ->where('shop_id = :shop_id')
            ->setParameter('shop_id', $shop->getId());

        $queryBuilder->execute();
    }

    public function saveAccessToken(ShopEntity $shop): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->update('shop')
            ->set('access_token', ':access_token')
            ->set('token_expires_in', ':token_expires_in')
            ->where('shop_id = :shop_id')
            ->setParameter('shop_id', $shop->getId())
            ->setParameter('access_token', $shop->getAccessToken())
            ->setParameter('token_expires_in', $shop->getTokenExpiresIn()->format('Y-m-d H:i:s'));

        $queryBuilder->execute();
    }
}