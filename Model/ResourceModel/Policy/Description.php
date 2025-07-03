<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ResourceModel\Policy;

class Description extends \M2E\Temu\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_IS_CUSTOM_TEMPLATE = 'is_custom_template';
    public const COLUMN_TITLE_MODE = 'title_mode';
    public const COLUMN_TITLE_TEMPLATE = 'title_template';
    public const COLUMN_DESCRIPTION_MODE = 'description_mode';
    public const COLUMN_DESCRIPTION_TEMPLATE = 'description_template';
    public const COLUMN_IMAGE_MAIN_MODE = 'image_main_mode';
    public const COLUMN_IMAGE_MAIN_ATTRIBUTE = 'image_main_attribute';
    public const COLUMN_GALLERY_TYPE = 'gallery_type';
    public const COLUMN_GALLERY_IMAGES_MODE = 'gallery_images_mode';
    public const COLUMN_GALLERY_IMAGES_LIMIT = 'gallery_images_limit';
    public const COLUMN_GALLERY_IMAGES_ATTRIBUTE = 'gallery_images_attribute';
    public const COLUMN_BULLET_POINTS = 'bullet_points';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct(): void
    {
        $this->_init(
            \M2E\Temu\Helper\Module\Database\Tables::TABLE_NAME_TEMPLATE_DESCRIPTION,
            self::COLUMN_ID
        );
    }
}
