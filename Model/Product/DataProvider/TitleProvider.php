<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\DataProvider;

class TitleProvider implements DataBuilderInterface
{
    use DataBuilderHelpTrait;

    public const NICK = 'Title';
    private const MAX_TITLE_LENGTH = 500;

    private string $onlineTitle;

    /**
     * @param \M2E\Temu\Model\Product $product
     *
     * @return string
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getTitle(\M2E\Temu\Model\Product $product): string
    {
        $title = $product->getDescriptionTemplateSource()->getTitle();

        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            $title = mb_substr($title, 0, self::MAX_TITLE_LENGTH);
        }

        $this->onlineTitle = $title;

        return $title;
    }

    public function getMetaData(): array
    {
        return [
            self::NICK => ['online_title' => $this->onlineTitle],
        ];
    }
}
