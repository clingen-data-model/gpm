<?php

namespace App\Actions\Utils;

class TransformArrayForCsv
{
    public function handle(iterable $data): string
    {
        $fd = fopen('php://temp', 'w+');
        $this->writeCsv($fd, $data);
        rewind($fd);
        $csv = stream_get_contents($fd);
        fclose($fd);
        return $csv;
    }

    public function stream(iterable $data, $fd): void
    {
        $this->writeCsv($fd, $data);
    }

    private function writeCsv($fd, iterable $data): void
    {
        $wroteHeader = false;
        foreach ($data as $row) {
            foreach ($row as $k => $v) {
                if (is_string($v)) {
                    $row[$k] = preg_replace('/\R/u', '; ', $v);
                }
            }
            if (!$wroteHeader) {
                fputcsv($fd, array_keys($row));
                $wroteHeader = true;
            }
            fputcsv($fd, $row);
        }
    }
}
