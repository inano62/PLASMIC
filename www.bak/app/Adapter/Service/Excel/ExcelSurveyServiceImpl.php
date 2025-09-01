<?php

namespace App\Adapter\Service\Excel;

use App\Application\Service\Excel\ExcelSurveyService;
use App\Domain\Object\SurveyCampaign\SurveyCampaignForImport;
use App\Domain\Object\SurveyCampaign\SurveyAnswerForImport;
use Illuminate\Support\Arr;
use App\Domain\Constant\ImportConstant;

class ExcelSurveyServiceImpl extends AbstractExcelService implements ExcelSurveyService
{
    /**
     * @param string $content
     * @return SurveyCampaignForImport|null
     */
    public function readForImport(string $content): ?SurveyCampaignForImport
    {
        $resource = $this->convertContentToResource($content);
        $spreadsheet = $this->readSpreadsheetByResource($resource);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $titleColumns = $sheet->rangeToArray("A1:{$highestColumn}1", NULL, FALSE, FALSE, FALSE)[0];

        $indexDict = $this->convertTitleColumnsToIndexDict($titleColumns);
        if (is_null($indexDict)) { return NULL; }

        $answers = [];
        for ($i = 2; $i <= $highestRow; $i++) {
            $values = $sheet->rangeToArray("A{$i}:{$highestColumn}{$i}", NULL, FALSE, FALSE, FALSE)[0];

            $uid = $this->normalizeInt($values[$indexDict->uidIndex]);
            $employeeCode = $this->normalizeInt($values[$indexDict->employeeCodeIndex]);
            $time = $this->normalizeTimestamp($values[$indexDict->timeIndex]);
            $quadrant = $this->normalizeInt($values[$indexDict->quadrantIndex]);

            if (!$this->hasNull([$uid, $employeeCode, $time, $quadrant])) {
                $answers[] = new SurveyAnswerForImport(
                    $uid,
                    $employeeCode,
                    $time,
                    $quadrant,
                );
            }
        }

        fclose($resource);

        if (!$answers) { return NULL; }

        $times = Arr::pluck($answers, 'time');

        return new SurveyCampaignForImport(
            min($times),
            max($times),
            $answers
        );
    }


    /**
     * @param array $titleColumns
     * @return ?object
     */
    private function convertTitleColumnsToIndexDict(array $titleColumns): ?object
    {
        //必要なタイトルが揃っているか確認、タイトルの実際の列番号を記録
        $indexDict = [];

        $uidIndex = $this->arraySearch('SAMPLEID', $titleColumns);
        $timeIndex = $this->arraySearch('ANSWERDATE', $titleColumns);
        $employeeCodeIndex = $this->arraySearch('F3_PRM', $titleColumns);
        $quadrantIndex = $this->arraySearch('20cell_ver2', $titleColumns);

        if ($this->hasNull([$uidIndex, $timeIndex, $employeeCodeIndex, $quadrantIndex])) {
            return NULL;
        }

        return (object)[
            'uidIndex' => $uidIndex,
            'employeeCodeIndex' => $employeeCodeIndex,
            'timeIndex' => $timeIndex,
            'quadrantIndex' => $quadrantIndex,
        ];
    }
}
