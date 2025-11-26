<?php

declare(strict_types=1);

namespace M2E\Temu\Setup\Update\y25_m11;

use M2E\Temu\Helper\Module\Database\Tables;

class AbilityToSaveSeveralTemplates extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->initTitleColumn();
        $this->fillTitleColumn();
        $this->modifyTitleColumn();
        $this->dropUniqueIndex();
    }

    private function initTitleColumn(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_CATEGORY_DICTIONARY);

        $modifier->addColumn(
            \M2E\Temu\Model\ResourceModel\Category\Dictionary::COLUMN_TITLE,
            'VARCHAR(255)',
            'NULL',
            \M2E\Temu\Model\ResourceModel\Category\Dictionary::COLUMN_CATEGORY_ID,
            false,
            false
        );

        $modifier->commit();
    }

    private function fillTitleColumn(): void
    {
        $dictionaryTableName = $this->getFullTableName(Tables::TABLE_NAME_CATEGORY_DICTIONARY);
        $query = $this->getConnection()
                      ->select()
                      ->from(['main_table' => $dictionaryTableName])
                      ->query();

        while ($row = $query->fetch()) {
            $pathParts = explode('>', $row['path']);
            $lastPathPart = trim(end($pathParts));
            $this->getConnection()->update(
                $dictionaryTableName,
                [
                    'title' => $lastPathPart,
                ],
                sprintf('id = %s', $row['id'])
            );
        }
    }

    private function modifyTitleColumn(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_CATEGORY_DICTIONARY);

        $modifier->changeColumn(
            \M2E\Temu\Model\ResourceModel\Category\Dictionary::COLUMN_TITLE,
            'VARCHAR(255) NOT NULL',
            null,
            null,
            false
        );

        $modifier->commit();
    }

    private function dropUniqueIndex(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_CATEGORY_DICTIONARY);
        $modifier->dropIndex('region__category_id', false);
        $modifier->commit();
    }
}
