<?php
declare(strict_types=1);

namespace Ergonode\ImporterErgonode1\Infrastructure\Action\Process\Product\Strategy;

use Ergonode\Attribute\Domain\ValueObject\AttributeCode;
use Ergonode\Attribute\Domain\ValueObject\AttributeType;
use Ergonode\Core\Domain\ValueObject\TranslatableString;
use Ergonode\Importer\Infrastructure\Action\Process\Product\Strategy\ImportProductAttributeStrategyInterface;
use Ergonode\Importer\Infrastructure\Exception\ImportException;
use Ergonode\ImporterErgonode1\Domain\Command\Import\ImportMultimediaFromDataCommand;
use Ergonode\ImporterErgonode1\Domain\Command\Import\ImportMultimediaFromUrlCommand;
use Ergonode\Multimedia\Domain\Query\MultimediaQueryInterface;
use Ergonode\SharedKernel\Domain\Aggregate\AttributeId;
use Ergonode\SharedKernel\Domain\Aggregate\MultimediaId;
use Ergonode\SharedKernel\Domain\Bus\CommandBusInterface;
use Ergonode\Value\Domain\ValueObject\ValueInterface;

abstract class AbstractImportProductImageAttributeStrategy implements ImportProductAttributeStrategyInterface
{
    public const MEDIA_URL = '%s/media/%s/%s';
    public const DATA_KEY_ID = 'id';
    public const DATA_KEY_APPURL = 'appUrl';
    public const DATA_KEY_NAME = 'name';
    public const DATA_KEY_MIME = 'mime';
    public const DATA_KEY_SIZE = 'size';
    public const DATA_KEY_HASH = 'hash';
    public const DATA_KEY_ALT = 'alt';
    public const DATA_KEY_EXTENSION = 'extension';
    public const DATA_KEYS = [
        self::DATA_KEY_ID,
        self::DATA_KEY_APPURL,
        self::DATA_KEY_NAME,
        self::DATA_KEY_MIME,
        self::DATA_KEY_SIZE,
        self::DATA_KEY_HASH,
        self::DATA_KEY_ALT,
        self::DATA_KEY_EXTENSION
    ];

    protected CommandBusInterface $commandBus;

    protected MultimediaQueryInterface $multimediaQuery;

    public function __construct(CommandBusInterface $commandBus, MultimediaQueryInterface $multimediaQuery)
    {
        $this->commandBus = $commandBus;
        $this->multimediaQuery = $multimediaQuery;
    }

    abstract public function supported(AttributeType $type): bool;

    abstract public function build(AttributeId $id, AttributeCode $code, TranslatableString $value): ValueInterface;

    protected function importMultimedia(string $image, AttributeCode $code): ?string
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $this->importFromUrl($image);
        }

        return $this->importFromJson($image, $code);
    }

    private function importFromJson(string $image, AttributeCode $code): ?string
    {
        $data = json_decode($image, true);
        if (!$data || !is_array($data)) {
            return null;
        }

        if (!empty(array_diff(self::DATA_KEYS, array_keys($data)))) {
            throw new ImportException(
                'Cannot import attribute {attribute}. Missing data',
                [
                    '{attribute}' => $code->getValue(),
                ],
            );
        }

        $alt = is_array($data[self::DATA_KEY_ALT]) ? $data[self::DATA_KEY_ALT] : [];
        $data[self::DATA_KEY_APPURL] = sprintf(
            self::MEDIA_URL,
            rtrim($data[self::DATA_KEY_APPURL], '/'),
            $data[self::DATA_KEY_ID],
            $data[self::DATA_KEY_HASH]
        );

        $command = new ImportMultimediaFromDataCommand(
            new MultimediaId($data[self::DATA_KEY_ID]),
            $data[self::DATA_KEY_APPURL],
            $data[self::DATA_KEY_NAME],
            $data[self::DATA_KEY_MIME],
            $data[self::DATA_KEY_SIZE],
            $data[self::DATA_KEY_HASH],
            $data[self::DATA_KEY_EXTENSION],
            new TranslatableString($alt)
        );

        $this->commandBus->dispatch($command, false);

        return $data[self::DATA_KEY_ID];
    }

    private function importFromUrl(string $image): string
    {
        $id = MultimediaId::generate();
        $filename = pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_FILENAME);
        $existingId = $this->multimediaQuery->findIdByFilename($filename);
        if ($existingId) {
            var_dump('return existing');
            return $existingId->getValue();
        }

        $command = new ImportMultimediaFromUrlCommand(
            $id,
            $image,
            $filename
        );
        $this->commandBus->dispatch($command, true);

        return $id->getValue();
    }
}
