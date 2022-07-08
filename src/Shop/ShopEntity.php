<?php declare(strict_types=1);

namespace App\Shop;

use DateTime;
use Shopware\AppBundle\Shop\ShopEntity as BaseEntity;

class ShopEntity extends BaseEntity
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var DateTime
     */
    private $tokenExpiresIn;

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    public function getTokenExpiresIn(): ?DateTime
    {
        return $this->tokenExpiresIn;
    }

    public function setTokenExpiresIn(DateTime $expiresIn): void
    {
        $this->tokenExpiresIn = $expiresIn;
    }
}
