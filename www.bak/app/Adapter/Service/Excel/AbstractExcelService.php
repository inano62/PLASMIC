<?php

namespace App\Adapter\Service\Excel;

use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

abstract class AbstractExcelService
{
    /**
     * @access protected
     * @param string $content
     * @return resource
     */
    protected function convertContentToResource(string $content)
    {
        $csv = tmpfile();
        fwrite($csv, $content);
        rewind($csv);

        return $csv;
    }

    /**
     * @access protected
     * @param resource $resource
     * @return Spreadsheet
     */
    protected function readSpreadsheetByResource($resource): Spreadsheet
    {
        $meta = stream_get_meta_data($resource);
        $path = $meta["uri"];

        return \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    }

    /**
     * @access protected
     * @param mixed $value
     * @param int[] $values
     * @return ?int
     */
    protected function arraySearch(mixed $value, array $values): ?int
    {
        $search = array_search($value, $values, TRUE);
        return $search !== FALSE ? $search : NULL;
    }

    /**
     * @access protected
     * @param ?numbers[] $values
     * @return bool
     */
    protected function hasNull(array $values): bool
    {
        foreach ($values as $value) {
            if (is_null($value)) { return TRUE; }
        }
        return FALSE;
    }

    /**
     * @access protected
     * @param mixed $value
     * @return ?int
     */
    protected function normalizeInt(mixed $value): ?int
    {
        if (is_null($value) || $value === '') {
            return NULL;
        } else {
            return (int)$value;
        }
    }

    /**
     * @access protected
     * @param mixed $value
     * @return ?int
     */
    protected function normalizeTimestamp(mixed $value): ?int
    {
        if (is_null($value) || $value === '') {
            return NULL;
        } else {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value);
        }
    }
}
