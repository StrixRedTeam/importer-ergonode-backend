<?php
declare(strict_types=1);

namespace Ergonode\ImporterErgonode1\Infrastructure\Action\Process\Product\Strategy;

use Ergonode\Attribute\Domain\Entity\Attribute\ImageAttribute;
use Ergonode\Importer\Infrastructure\Exception\ImportException;
use Ergonode\SharedKernel\Domain\Aggregate\AttributeId;
use Ergonode\Attribute\Domain\ValueObject\AttributeCode;
use Ergonode\Core\Domain\ValueObject\TranslatableString;
use Ergonode\Value\Domain\ValueObject\TranslatableStringValue;
use Ergonode\Value\Domain\ValueObject\ValueInterface;
use Ergonode\Attribute\Domain\ValueObject\AttributeType;

class ImportProductImageAttributeStrategy extends AbstractImportProductImageAttributeStrategy
{
    public function supported(AttributeType $type): bool
    {
        return ImageAttribute::TYPE === $type->getValue();
    }

    public function build(AttributeId $id, AttributeCode $code, TranslatableString $value): ValueInterface
    {
        $multimediaId = null;
        $translations = $value->getTranslations();
        $key = array_key_first($translations);
        if (!$key) {
            throw new ImportException(
                'Cannot import attribute {attribute}. Missing data',
                [
                    '{attribute}' => $code->getValue(),
                ],
            );
        }

        $singleLangContent = $translations[$key];
        $images = explode(';', $singleLangContent);
        foreach ($images as $image) {
            $multimediaId = $this->importMultimedia($image, $code);
        }

        $result = [$key => $multimediaId];

        return new TranslatableStringValue(new TranslatableString($result));
    }
}
