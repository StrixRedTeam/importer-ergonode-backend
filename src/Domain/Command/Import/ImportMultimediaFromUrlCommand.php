<?php
declare(strict_types=1);

namespace Ergonode\ImporterErgonode1\Domain\Command\Import;

use Ergonode\Importer\Domain\Command\ImporterCommandInterface;
use Ergonode\SharedKernel\Domain\Aggregate\MultimediaId;

class ImportMultimediaFromUrlCommand implements ImporterCommandInterface
{
    protected MultimediaId $id;

    protected string $filename;

    private string $url;

    public function __construct(
        MultimediaId $id,
        string $url,
        string $filename
    ) {
        $this->url = $url;
        $this->id = $id;
        $this->filename = $filename;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getId(): MultimediaId
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

}
