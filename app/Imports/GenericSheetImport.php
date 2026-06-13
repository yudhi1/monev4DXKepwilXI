<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Import generik: mengembalikan seluruh baris sheet pertama apa adanya
 * (baris pertama biasanya header). Dipakai untuk preview data Excel
 * tanpa perlu tahu struktur kolomnya terlebih dahulu.
 */
class GenericSheetImport implements ToArray
{
    public function array(array $array): array
    {
        return $array;
    }
}
