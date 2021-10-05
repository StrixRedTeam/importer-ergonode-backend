<?php
declare(strict_types=1);

namespace Ergonode\ImporterErgonode1\Infrastructure\Handler\Multimedia;

use Ergonode\Core\Infrastructure\Service\DownloaderInterface;
use Ergonode\Importer\Infrastructure\Exception\ImportException;
use Ergonode\ImporterErgonode1\Domain\Command\Import\ImportMultimediaFromUrlCommand;
use Ergonode\Multimedia\Domain\Entity\Multimedia;
use Ergonode\Multimedia\Domain\Query\MultimediaQueryInterface;
use Ergonode\Multimedia\Domain\Repository\MultimediaRepositoryInterface;
use Ergonode\Multimedia\Infrastructure\Service\HashCalculationServiceInterface;
use Ergonode\SharedKernel\Domain\AggregateId;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\File;

class ImportMultimediaFromUrlCommandHandler
{
    protected DownloaderInterface $downloader;

    protected FilesystemInterface $multimediaStorage;

    protected MultimediaQueryInterface $multimediaQuery;

    protected HashCalculationServiceInterface $hashService;

    private MultimediaRepositoryInterface $repository;

    public function __construct(
        MultimediaRepositoryInterface $repository,
        DownloaderInterface $downloader,
        FilesystemInterface $multimediaStorage,
        MultimediaQueryInterface $multimediaQuery,
        HashCalculationServiceInterface $hashService
    ) {
        $this->repository = $repository;
        $this->downloader = $downloader;
        $this->multimediaStorage = $multimediaStorage;
        $this->multimediaQuery = $multimediaQuery;
        $this->hashService = $hashService;
    }

    public function __invoke(ImportMultimediaFromUrlCommand $command): void
    {
        $url = $command->getUrl();

        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

        $remoteFilename = $command->getFilename();
        if (!$remoteFilename || !$extension) {
            throw new ImportException(
                'Cannot import media {id} from {url}.',
                ['{id}' => $command->getId()->getValue(), '{url}' => $command->getUrl()]
            );
        }

        $existingId = $this->multimediaQuery->findIdByFilename($remoteFilename);

        if (!$existingId) {
            $tmpFile = tempnam(sys_get_temp_dir(), AggregateId::generate()->getValue());

            $content = $this->downloader->download($url);
            file_put_contents($tmpFile, $content);
            $file = new File($tmpFile);

            $hash = $this->hashService->calculateHash($file);
            $filename = sprintf('%s.%s', $hash->getValue(), $extension);

            if (!$this->multimediaStorage->has($filename)) {
                $this->multimediaStorage->write($filename, $content);
            }

            $size = $this->multimediaStorage->getSize($filename);
            $mime = $this->multimediaStorage->getMimetype($filename);

            $multimedia = new Multimedia(
                $command->getId(),
                $remoteFilename,
                $extension,
                $size,
                $hash,
                $mime,
            );

            $this->repository->save($multimedia);
            unlink($tmpFile);
        }
    }
}
