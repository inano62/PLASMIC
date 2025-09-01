<?php

namespace App\Adapter\Service\Excel;

use Illuminate\Http\UploadedFile;
use App\Application\Service\Excel\ExcelUserService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use App\Domain\Object\Excel\ExcelUserForImport;
use App\Domain\Object\Excel\ExcelUserColumnGroup;

class ExcelUserServiceImpl implements ExcelUserService
{
    public function readForImport(string $content): ?array
    {
        $resource = $this->convertStringToResource($content);
        $excel = $this->readExcelByResource($resource);
        $sheet = $excel->getActiveSheet();

        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();
        $jinjiData = [];
        $columnGroup = NULL;
        for ($i = 1; $i <= $lastRow; $i++) {
            $row = $sheet->rangeToArray("A{$i}:{$lastColumn}{$i}", NULL, FALSE, FALSE, FALSE)[0];

            if(is_null($columnGroup)) {
                if(!$this->isValidTitleColumns($row)) { break; }
                $row = array_map([$this, 'normalizeHeadString'], $row);

                $employeeNumberIndex = $this->arraySearch(['ユーザID'], $row);
                $honmuBumonCodeIndex = $this->arraySearch(['本務部門コード'], $row);
                $honmuBumonNameIndex = $this->arraySearch(['本務部門名称'], $row);
                $honmuBushoCodeIndex = $this->arraySearch(['本務部署コード'], $row);
                $honmuBushoNameIndex = $this->arraySearch(['本務部署名称'], $row);
                if($employeeNumberIndex === false || $honmuBumonCodeIndex === false
                    || $honmuBumonNameIndex === false || $honmuBushoCodeIndex === false || $honmuBushoNameIndex === false){
                    return NULL;
                }
                $columnGroup = new ExcelUserColumnGroup(
                    $employeeNumberIndex,
                    $honmuBumonCodeIndex,
                    $honmuBumonNameIndex,
                    $honmuBushoCodeIndex,
                    $honmuBushoNameIndex
                );

            } else {
                $jinjiData[] = new ExcelUserForImport(
                    $this->normalizeInt($row[$columnGroup->employeeNumberIndex]),
                    $this->normalizeInt($row[$columnGroup->honmuBumonCodeIndex]),
                    $this->normalizeString($row[$columnGroup->honmuBumonNameIndex]),
                    $this->normalizeInt($row[$columnGroup->honmuBushoCodeIndex]),
                    $this->normalizeString($row[$columnGroup->honmuBushoNameIndex]),
                );
            }

        }
        return $jinjiData;

    }

    /**
     * @access private
     * @param ?string $value
     * @return ?string
     */
    private function normalizeString(?string $value): ?string
    {
        return !is_null($value) && $value !== '' ? (string)$value : NULL;
    }

    /**
     * @access
     * @param ?string $value
     * @return ?int
     */
    private function normalizeInt(?string $value): ?int
    {
        return !is_null($value) && $value !== '' ? (int)$value : NULL;
    }

    /**
     * @access private
     * @param UploadedFile $file
     * @return resource
     */
    private function convertStringToResource(string $content)
    {
        $resource = tmpfile();
        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }

    private function readExcelByResource($resource)
    {
        $meta = stream_get_meta_data($resource);
        $path = $meta["uri"];
        $reader = new XlsxReader();
        return $reader->load($path);
    }

    /**
     * @param ?string $value
     * @return ?string
     */
    private function normalizeHeadString(?string $value): ?string
    {
        if (is_null($value)) { return NULL; }
        if ($value === '') { return NULL; }

        $value = str_replace(['（','）'], ['(',')'], $value);

        return $value;
    }

    /**
     * @param string[] $searches
     * @param string[] $haystack
     * @return ?int
     */
    private function arraySearch(array $searches, array $haystack): ?int
    {
        foreach ($searches as $search) {
            if (($i = array_search($search, $haystack, TRUE)) !== FALSE) {
                return $i;
            }
        }
        return NULL;
    }

    /**
     * @param string[] $titleColumns
     * @return bool
     */
    private function isValidTitleColumns(array $titleColumns): bool
    {

        if(!is_null($this->arraySearch(['ユーザID'], $titleColumns))
            && !is_null($this->arraySearch(['本務部門コード'], $titleColumns))
            && !is_null($this->arraySearch(['本務部門名称'], $titleColumns))
            && !is_null($this->arraySearch(['本務部署コード'], $titleColumns))
            && !is_null($this->arraySearch(['本務部署名称'], $titleColumns))
        ){
            return true;
        }else{
            return false;
        }
    }
}
