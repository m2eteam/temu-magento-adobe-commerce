<?php

declare(strict_types=1);

namespace M2E\Temu\Helper\Module;

class Maintenance implements \M2E\Core\Model\Module\MaintenanceInterface
{
    public const MENU_ROOT_NODE_NICK = 'M2E_Temu::temu_maintenance';

    private const MAINTENANCE_CONFIG_PATH = 'maintenance';

    private \M2E\Core\Model\Module\Maintenance\AdapterFactory $adapterFactory;
    private \M2E\Core\Model\Module\Maintenance\Adapter $adapter;

    public function __construct(
        \M2E\Core\Model\Module\Maintenance\AdapterFactory $adapterFactory
    ) {
        $this->adapterFactory = $adapterFactory;
    }

    public function enable(): void
    {
        $this->getAdapter()->enable();
    }

    public function isEnabled(): bool
    {
        return $this->getAdapter()->isEnabled();
    }

    public function enableDueLowMagentoVersion(): void
    {
        $this->getAdapter()->enableDueLowMagentoVersion();
    }

    public function isEnabledDueLowMagentoVersion(): bool
    {
        return $this->getAdapter()->isEnabledDueLowMagentoVersion();
    }

    public function disable(): void
    {
        $this->getAdapter()->disable();
    }

    // ----------------------------------------

    private function getAdapter(): \M2E\Core\Model\Module\Maintenance\Adapter
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->adapter)) {
            $this->adapter = $this->adapterFactory->create(
                $this->getMaintenanceConfigPath(),
            );
        }

        return $this->adapter;
    }

    private function getMaintenanceConfigPath(): string
    {
        return \M2E\Temu\Setup\MagentoCoreConfigSettings::MAGENTO_CORE_CONFIG_PREFIX
            . '/' . self::MAINTENANCE_CONFIG_PATH;
    }
}
