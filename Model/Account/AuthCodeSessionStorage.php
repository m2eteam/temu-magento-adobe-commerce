<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Account;

class AuthCodeSessionStorage
{
    private \M2E\Temu\Helper\Data\Session $sessionHelper;

    public function __construct(\M2E\Temu\Helper\Data\Session $sessionHelper)
    {
        $this->sessionHelper = $sessionHelper;
    }

    public function setAccount(string $authCode, int $accountId): void
    {
        $this->sessionHelper->setValue($this->getKey($authCode), $accountId);
    }

    public function getAccount(string $authCode): ?int
    {
        return $this->sessionHelper->getValue($this->getKey($authCode));
    }

    private function getKey(string $authCode): string
    {
        return \M2E\Core\Helper\Data::md5String($authCode);
    }
}
