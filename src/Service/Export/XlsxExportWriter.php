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
     * @return string the name of the written file, relative to the export storage
     */
    public function write(ExportJob $job): string
    {
        $exporter = $this->exporterFor($job->getType());

        $this->storage->prepare();

        $fileName = $job->getType()->value . '_' . $job->getId() . '_' . (new \DateTimeImmutable())->format('YmdHis') . '.xlsx';

        $writer = new Writer();

        try {
            $writer->openToFile($this->storage->pathOf($fileName));
            $writer->addRow(Row::fromValues($exporter->headers()));

            foreach ($exporter->rows($job) as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        } catch (\Throwable $exception) {
            $this->discard($writer, $fileName);

            throw $exception;
        }

        return $fileName;
    }

    /**
     * A failed export leaves no half-written file behind.
     */
    private function discard(Writer $writer, string $fileName): void
    {
        try {
            $writer->close();
        } catch (\Throwable) {
            // The writer was never opened or already failed: only the file is left to remove.
        }

        $this->storage->delete($fileName);
    }

    private function exporterFor(ExportType $type): Exporter
    {
        return $this->exporters[$type->value] ?? throw new \LogicException(sprintf('No exporter registered for the "%s" export.', $type->value));
    }
}
