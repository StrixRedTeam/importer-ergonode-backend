<?php
declare(strict_types=1);

namespace Ergonode\ImporterErgonode1\Infrastructure\Handler\Multimedia;

use Ergonode\Core\Infrastructure\Service\DownloaderInterface;
use Ergonode\Importer\Infrastructure\Exception\ImportException;
use Ergonode\ImporterErgonode1\Domain\Command\Import\ImportMultimediaFromDataCommand;
use Ergonode\Multimedia\Domain\Entity\Multimedia;
use Ergonode\Multimedia\Domain\Repository\MultimediaRepositoryInterface;
use Ergonode\Multimedia\Domain\ValueObject\Hash;
use League\Flysystem\FilesystemInterface;

class ImportMultimediaFromDataCommandHandler
{
    protected DownloaderInterface $downloader;

    protected FilesystemInterface $multimediaStorage;

    private MultimediaRepositoryInterface $repository;

    public function __construct(
        MultimediaRepositoryInterface $repository,
        DownloaderInterface $downloader,
        FilesystemInterface $multimediaStorage
    ) {
        $this->repository = $repository;
        $this->downloader = $downloader;
        $this->multimediaStorage = $multimediaStorage;
    }

    public function __invoke(ImportMultimediaFromDataCommand $command): void
    {
        if ($this->repository->exists($command->getId())) {
            return;
        }

        $url = $command->getUrl();
        $extension = $command->getExtension();

        $filename = sprintf('%s.%s', $command->getHash(), $extension);
        if (!$this->multimediaStorage->has($filename)) {
            $content = $this->downloader->download($url);
            if (!$content) {
                throw new ImportException(
                    'Cannot import media {id} from {url}.',
                    ['{id}' => $command->getId()->getValue(), '{url}' => $command->getUrl()]
                );
            }
            $this->multimediaStorage->write($filename, $content);
        }

        $multimedia = new Multimedia(
            $command->getId(),
            $command->getName(),
            $extension,
            $command->getSize(),
            new Hash($command->getHash()),
            $command->getMime(),
        );

        $this->repository->save($multimedia);
    }
}
