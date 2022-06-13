<?php

namespace App\Actions\Utils;


class TransformArrayForCsv
{
    public function handle(array $data): string
    {
        $rows = [];
        $fd = fopen('php://memory', 'rw');
        foreach ($data as $row) {
            fputcsv($fd, $row);
            rewind($fd);
            $rows[] = trim(fgets($fd));
            rewind($fd);
        }
        fclose($fd);

        return implode("\n", $rows);
    }
}