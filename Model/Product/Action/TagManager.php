<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action;

class TagManager
{
    private \M2E\Temu\Model\TagFactory $tagFactory;
    private \M2E\Temu\Model\Tag\ListingProduct\Buffer $tagBuffer;
    private \M2E\Temu\Model\Tag\ValidatorIssues $validatorIssues;

    public function __construct(
        \M2E\Temu\Model\TagFactory $tagFactory,
        \M2E\Temu\Model\Tag\ListingProduct\Buffer $tagBuffer,
        \M2E\Temu\Model\Tag\ValidatorIssues $validatorIssues
    ) {
        $this->tagFactory = $tagFactory;
        $this->tagBuffer = $tagBuffer;
        $this->validatorIssues = $validatorIssues;
    }

    /**
     * @param \M2E\Temu\Model\Product $product
     * @param \M2E\Temu\Model\Product\Action\Validator\ValidatorMessage[] $messages
     */
    public function addErrorTags(\M2E\Temu\Model\Product $product, array $messages): void
    {
        if (empty($messages)) {
            return;
        }

        $tags = [];

        $userErrors = array_filter($messages, function ($message) {
            return $message->getCode() !== \M2E\Temu\Model\Tag\ValidatorIssues::NOT_USER_ERROR;
        });

        if (!empty($userErrors)) {
            foreach ($userErrors as $userError) {
                $error = $this->validatorIssues->mapByCode($userError->getCode());
                if ($error === null) {
                    continue;
                }

                $tags[] = $this->tagFactory->createByErrorCode(
                    $error->getCode(),
                    $error->getText()
                );
            }

            $this->tagBuffer->addTags($product, $tags);
        }
    }
}
