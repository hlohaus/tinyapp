<?php declare(strict_types=1);

namespace App\Shop;

use DateInterval;
use DateTime;
use Shopware\AppBundle\Shop\ShopEntity as BaseEntity;

class ShopEntity extends BaseEntity
{
    private ?string $accessToken;

    private ?DateTime $tokenExpiresIn = null;
    private string $proxyUrl;

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

    public function setTokenExpiresIn(?DateTime $expiresIn): void
    {
        $this->tokenExpiresIn = $expiresIn;
    }
    
    public function setTokenExpiresInFromSeconds(int $expiresIn): void
    {
        $this->tokenExpiresIn = new DateTime();
        $this->tokenExpiresIn->add(new DateInterval('PT' . $expiresIn . 'S'));
    }

    public function isAccessTokenFresh(): bool
    {
        if (!isset($this->accessToken)) {
            return false;
        }
        // It's in the next 30 seconds still fresh?
        $now = new DateTime();
        $now->sub(new DateInterval('PT30S'));
        return $this->getTokenExpiresIn() > $now;
    }

    public function setProxyUrl(string $proxyUrl): void
    {
        $this->proxyUrl = $proxyUrl;
    }

    public function getUrl(): string
    {
        return $this->proxyUrl ?? parent::getUrl();
    }
}
