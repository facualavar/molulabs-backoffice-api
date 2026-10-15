<?php

namespace App\Service\Export;

use App\Entity\ExportJob;
use App\Enum\ExportType;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class XlsxExportWriter
{
    /**
     * @var array<string, Exporter> keyed by export type value
     */
    private array $exporters = [];

    /**
     * @param iterable<Exporter> $exporters
     */
    public function __construct(
        #[AutowireIterator(Exporter::TAG)] iterable $exporters,
        private readonly ExportStorage $storage,
    ) {
        foreach ($exporters as $exporter) {
            $this->exporters[$exporter->type()->value] = $exporter;
        }
    }

    /**
     * @return string the name of the file written to the export storage
     */
    public function write(ExportJob $job): string
    {
        $exporter = $this->exporterFor($job->getType());

        $fileName = $job->getType()->value . '_' . $job->getId() . '_' . (new \DateTimeImmutable())->format('YmdHis') . '.xlsx';

        // OpenSpout writes to a local file; it is uploaded only once complete, so a failed export
        // leaves nothing in the storage.
        $localPath = tempnam(sys_get_temp_dir(), 'export_');
        $writer = new Writer();

        try {
            $writer->openToFile($localPath);
            $writer->addRow(Row::fromValues($exporter->headers()));

            foreach ($exporter->rows($job) as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();

            $this->storage->upload($fileName, $localPath);
        } catch (\Throwable $exception) {
            $this->closeQuietly($writer);

            throw $exception;
        } finally {
            unlink($localPath);
        }

        return $fileName;
    }

    private function closeQuietly(Writer $writer): void
    {
        try {
            $writer->close();
        } catch (\Throwable) {
            // The writer was never opened or already closed: nothing else to release.
        }
    }

    private function exporterFor(ExportType $type): Exporter
    {
        return $this->exporters[$type->value] ?? throw new \LogicException(sprintf('No exporter registered for the "%s" export.', $type->value));
    }
}
