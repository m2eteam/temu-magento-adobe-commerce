<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y25_m08;

use M2E\Temu\Helper\Module\Database\Tables;
use M2E\Temu\Model\ResourceModel\Listing\Wizard as ListingWizard;
use M2E\Temu\Model\ResourceModel\Listing\Wizard\Step as ListingWizardStep;

class CompleteWizards extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $connection = $this->getConnection();

        $connection->update(
            $this->getFullTableName(Tables::TABLE_NAME_LISTING_WIZARD),
            [ListingWizard::COLUMN_IS_COMPLETED => 1],
            [ListingWizard::COLUMN_IS_COMPLETED . ' = ?' => 0]
        );

        $connection->update(
            $this->getFullTableName(Tables::TABLE_NAME_LISTING_WIZARD_STEP),
            [ListingWizardStep::COLUMN_IS_COMPLETED => 1],
            [ListingWizardStep::COLUMN_IS_COMPLETED . ' = ?' => 0]
        );

        $connection->delete($this->getFullTableName(Tables::TABLE_NAME_LISTING_WIZARD_PRODUCT));
    }
}
