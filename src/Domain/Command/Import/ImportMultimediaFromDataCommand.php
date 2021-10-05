<?php

declare(strict_types=1);

namespace Ergonode\ImporterErgonode1\Domain\Command\Import;

use Ergonode\Core\Domain\ValueObject\TranslatableString;
use Ergonode\Importer\Domain\Command\ImporterCommandInterface;
use Ergonode\SharedKernel\Domain\Aggregate\MultimediaId;

class ImportMultimediaFromDataCommand implements ImporterCommandInterface
{
    protected MultimediaId $id;

    protected string $mime;

    protected int $size;

    protected string $hash;

    protected TranslatableString $alt;

    protected string $extension;

    private string $url;

    private string $name;

    public function __construct(
        MultimediaId $id,
        string $url,
        string $name,
        string $mime,
        int $size,
        string $hash,
        string $extension,
        TranslatableString $alt
    ) {
        $this->url = $url;
        $this->name = $name;
        $this->id = $id;
        $this->mime = $mime;
        $this->size = $size;
        $this->hash = $hash;
        $this->alt = $alt;
        $this->extension = $extension;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): MultimediaId
    {
        return $this->id;
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getAlt(): TranslatableString
    {
        return $this->alt;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
