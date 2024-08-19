<?php

namespace App\Actions\Utils;

class TransformArrayForCsv
{
    /**
     * Transforms an array of associative arrays into a CSV string.
     *
     * @param array $data The data to transform.
     * @return string The CSV formatted string.
     */
    public function handle(array $data): string
    {
        $rows = [];
        
        // Open a memory stream to hold CSV data temporarily
        $fd = fopen('php://memory', 'rw');
        foreach ($data as $row) {
            // Write the header row if not already written
            if (count($rows) == 0) {
                $this->pushRow(array_keys($row), $rows, $fd);
            }
            // Write the data row
            $this->pushRow($row, $rows, $fd);
        }
        fclose($fd); // Close the memory stream

        // Join all rows into a single string separated by newlines
        return implode("\n", $rows);
    }

    /**
     * Writes a row to the CSV and stores it in the rows array.
     *
     * @param array $row The row data.
     * @param array &$rows Reference to the rows array.
     * @param resource $fd The file descriptor.
     */
    private function pushRow(array $row, array &$rows, $fd)
    {
        fputcsv($fd, $row); // Write the CSV row to memory
        rewind($fd); // Rewind the stream to read the contents
        $rows[] = trim(fgets($fd)); // Read the written line, trim, and store it
        rewind($fd); // Rewind again for the next write
    }
}
