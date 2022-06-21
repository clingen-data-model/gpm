<?php

namespace App\Actions\Utils;


class TransformArrayForCsv
{
    public function handle(array $data): string
    {
        $rows = [];
        
        $fd = fopen('php://memory', 'rw');
        foreach ($data as $row) {
            if (count($rows) == 0) {
                $this->pushRow(array_keys($row), $rows, $fd);
            }
            $this->pushRow($row, $rows, $fd);
        }
        fclose($fd);

        return implode("\n", $rows);
    }

    private function pushRow(array $row, array &$rows, &$fd)
    {
        fputcsv($fd, $row);
        rewind($fd);
        $rows[] = trim(fgets($fd));
        rewind($fd);
    }
    
}