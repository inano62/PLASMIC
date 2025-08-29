<?php

namespace App\Adapter\Service\Csv;

use App\Application\Service\Csv\CsvTime3xService;
use App\Domain\Object\Time3x\TimecardForImport;
use App\Domain\Object\Time3x\TimecardColumnGroup;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class CsvTime3xServiceImpl extends AbstractCsvService implements CsvTime3xService
{
    public function readForImport(string $content): ?array
    {
        $resource = $this->convertContentToResource($content);
        $spreadsheet = $this->readUtf8CsvByResource($resource);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $timecards = [];
        $TimecardColumnGroup = NULL;
        for ($i = 1; $i <= $highestRow; $i++) {
            $values = $sheet->rangeToArray("A{$i}:{$highestColumn}{$i}", NULL, FALSE, FALSE, FALSE)[0];
            $values = array_map(function($value) { return $value !== '' ? $value : NULL; }, $values);

            if( is_null($TimecardColumnGroup) ) {

                if (!$this->isValidTitleColumns($values)) { return NULL; }

                $companyCodeIndex = $this->arraySearch(['会社コード'], $values);
                $companyNameIndex = $this->arraySearch(['会社名称'], $values);
                $bushoCodeIndex = $this->arraySearch(['所属'], $values);
                $bushoNameIndex = $this->arraySearch(['所属名称'], $values);
                $employeeCodeIndex = $this->arraySearch(['従業員番号'], $values);
                $nameIndex = $this->arraySearch(['氏名(漢字)'], $values);
                $dateIndex = $this->arraySearch(['年月日'], $values);
                $holidayCodeIndex = $this->arraySearch(['休日区分(コード)'], $values);
                $holidayNameIndex = $this->arraySearch(['休日区分名称'], $values);
                $workTypeCodeIndex = $this->arraySearch(['勤務区分'], $values);
                $workTypeNameIndex = $this->arraySearch(['勤務区分名称'], $values);
                $staffTypeCodeIndex = $this->arraySearch(['社員区分'], $values);
                $staffTypeNameIndex = $this->arraySearch(['社員区分名称'], $values);
                $checkInIndex = $this->arraySearch(['打刻出社'], $values);
                $checkOutIndex = $this->arraySearch(['打刻退社'], $values);
                $inIndex = $this->arraySearch(['出社'], $values);
                $outIndex = $this->arraySearch(['退社'], $values);
                $workTimeIndex = $this->arraySearch(['総労働'], $values);
                $compTimeIndex = $this->arraySearch(['代休時間'], $values);
                $overworkTimeIndex = $this->arraySearch(['所定以外'], $values);
                $holidayTimeIndex = $this->arraySearch(['休日時間'], $values);
                $delayTimeIndex = $this->arraySearch(['遅刻時間'], $values);
                $breakTimeIndex = $this->arraySearch(['非勤務時'], $values);
                $graveyardTimeIndex = $this->arraySearch(['深夜時間'], $values);
                $exemptTimeIndex = $this->arraySearch(['単月裁労'], $values);

                $TimecardColumnGroup = new TimecardColumnGroup(
                    $companyCodeIndex,
                    $companyNameIndex,
                    $bushoCodeIndex,
                    $bushoNameIndex,
                    $employeeCodeIndex,
                    $nameIndex,
                    $dateIndex,
                    $holidayCodeIndex,
                    $holidayNameIndex,
                    $workTypeCodeIndex,
                    $workTypeNameIndex,
                    $staffTypeCodeIndex,
                    $staffTypeNameIndex,
                    $checkInIndex,
                    $checkOutIndex,
                    $inIndex,
                    $outIndex,
                    $workTimeIndex,
                    $compTimeIndex,
                    $overworkTimeIndex,
                    $holidayTimeIndex,
                    $delayTimeIndex,
                    $breakTimeIndex,
                    $graveyardTimeIndex,
                    $exemptTimeIndex
                );

            }else{

                $timecards[] = new TimecardForImport(
                    $values[$TimecardColumnGroup->companyCodeIndex],
                    $values[$TimecardColumnGroup->companyNameIndex],
                    $values[$TimecardColumnGroup->bushoCodeIndex],
                    $values[$TimecardColumnGroup->bushoNameIndex],
                    $values[$TimecardColumnGroup->employeeCodeIndex],
                    $values[$TimecardColumnGroup->nameIndex],
                    $values[$TimecardColumnGroup->dateIndex],
                    $values[$TimecardColumnGroup->holidayCodeIndex],
                    $values[$TimecardColumnGroup->holidayNameIndex],
                    $values[$TimecardColumnGroup->workTypeCodeIndex],
                    $values[$TimecardColumnGroup->workTypeNameIndex],
                    $values[$TimecardColumnGroup->staffTypeCodeIndex],
                    $values[$TimecardColumnGroup->staffTypeNameIndex],
                    $this->ConvertHourToMinute($values[$TimecardColumnGroup->checkInIndex]),
                    $this->ConvertHourToMinute($values[$TimecardColumnGroup->checkOutIndex]),
                    $this->ConvertHourToMinute($values[$TimecardColumnGroup->inIndex]),
                    $this->ConvertHourToMinute($values[$TimecardColumnGroup->outIndex]),
                    $values[$TimecardColumnGroup->workTimeIndex],
                    $values[$TimecardColumnGroup->compTimeIndex],
                    $values[$TimecardColumnGroup->overworkTimeIndex],
                    $values[$TimecardColumnGroup->holidayTimeIndex],
                    $values[$TimecardColumnGroup->delayTimeIndex],
                    $values[$TimecardColumnGroup->breakTimeIndex],
                    $values[$TimecardColumnGroup->graveyardTimeIndex],
                    $values[$TimecardColumnGroup->exemptTimeIndex],
                );

            }
        }

        fclose($resource);

        return $timecards;
    }

    /**
     * @param string[] $titleColumns
     * @return bool
     */
    private function isValidTitleColumns(array $titleColumns): bool
    {
        if(is_null( $this->arraySearch(['会社コード'], $titleColumns) ) ||
            is_null( $this->arraySearch(['会社名称'], $titleColumns) ) ||
            is_null( $this->arraySearch(['所属'], $titleColumns) ) ||
            is_null( $this->arraySearch(['所属名称'], $titleColumns) ) ||
            is_null( $this->arraySearch(['従業員番号'], $titleColumns) ) ||
            is_null( $this->arraySearch(['氏名(漢字)'], $titleColumns) ) ||
            is_null( $this->arraySearch(['年月日'], $titleColumns) ) ||
            is_null( $this->arraySearch(['休日区分(コード)'], $titleColumns) ) ||
            is_null( $this->arraySearch(['休日区分名称'], $titleColumns) ) ||
            is_null( $this->arraySearch(['勤務区分'], $titleColumns) ) ||
            is_null( $this->arraySearch(['勤務区分名称'], $titleColumns) ) ||
            is_null( $this->arraySearch(['社員区分'], $titleColumns) ) ||
            is_null( $this->arraySearch(['社員区分名称'], $titleColumns) ) ||
            is_null( $this->arraySearch(['打刻出社'], $titleColumns) ) ||
            is_null( $this->arraySearch(['打刻退社'], $titleColumns) ) ||
            is_null( $this->arraySearch(['出社'], $titleColumns) ) ||
            is_null( $this->arraySearch(['退社'], $titleColumns) ) ||
            is_null( $this->arraySearch(['総労働'], $titleColumns) ) ||
            is_null( $this->arraySearch(['代休時間'], $titleColumns) ) ||
            is_null( $this->arraySearch(['所定以外'], $titleColumns) ) ||
            is_null( $this->arraySearch(['休日時間'], $titleColumns) ) ||
            is_null( $this->arraySearch(['遅刻時間'], $titleColumns) ) ||
            is_null( $this->arraySearch(['非勤務時'], $titleColumns) ) ||
            is_null( $this->arraySearch(['深夜時間'], $titleColumns) ) ||
            is_null( $this->arraySearch(['単月裁労'], $titleColumns) )
        ){
            return false;
        }else{
            return true;
        }
    }

    private function ConvertHourToMinute(?string $time): ?int
    {
        if(is_null($time)){
            return null;
        }

        $timeArray = explode(':',$time);
        $hour = (int)$timeArray[0] * 60;

        return $hour + (int)$timeArray[1];
    }

    private function readUtf8CsvByResource($resource){
        $meta = stream_get_meta_data($resource);
        $path = $meta["uri"];
        $reader = new Csv;

        return $reader->load($path);
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

}
