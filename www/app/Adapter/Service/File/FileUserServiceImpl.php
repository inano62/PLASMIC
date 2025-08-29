<?php

namespace App\Adapter\Service\File;

use Illuminate\Http\UploadedFile;
use App\Application\Service\File\FileUserService;
use App\Domain\Object\User\UserImportColumnGroup;
use App\Domain\Object\User\UserForImport;

class FileUserServiceImpl implements FileUserService
{
    /**
     * @param UploadedFile $file
     * @return string
     */
    public function readContent(UploadedFile $file): string
    {
        $sjisContent = $file->get();
        return mb_convert_encoding($sjisContent, 'UTF-8', 'SJIS-win');
    }

    /**
     * @param UploadedFile $file
     * @return [string[][], \App\Domain\Object\User\UserImportColumnGroup[]]
     */
    public function readForImport(string $content): array
    {
        $csv = $this->convertStringToResource($content);

        $rows = [];
        $columnGroup = NULL;

        while ($row = fgetcsv($csv)) {
            if (is_null($columnGroup)) {

                $row = array_map([$this, 'normalizeHeadString'], $row);

                $employeeCodeIndex = $this->arraySearch(['ユーザID'], $row);
                $previousEmployeeCodeIndex = $this->arraySearch(['(旧)ユーザID'], $row);
                $displayNameIndex = $this->arraySearch(['氏名'], $row);
                $surnameKanjiIndex = $this->arraySearch(['氏名(姓)'], $row);
                $forenameKanjiIndex = $this->arraySearch(['氏名(名)'], $row);
                $surnameKatakanaIndex = $this->arraySearch(['カナ氏名(姓)'], $row);
                $forenameKatakanaIndex = $this->arraySearch(['カナ氏名(名)'], $row);
                $surnameAlphabetIndex = $this->arraySearch(['アルファベット氏名(姓)'], $row);
                $forenameAlphabetIndex = $this->arraySearch(['アルファベット氏名(名)'], $row);
                $emailIndex = $this->arraySearch(['メインメールアドレス'], $row);
                $bumonGroupCodeIndex = $this->arraySearch(['部門グループコード'], $row);
                $bumonGroupNameIndex = $this->arraySearch(['部門グループ名称'], $row);
                $bumonCodeIndex = $this->arraySearch(['部門コード'], $row);
                $bumonNameIndex = $this->arraySearch(['部門名称'], $row);
                $bushoCodeIndex = $this->arraySearch(['部署コード'], $row);
                $bushoNameIndex = $this->arraySearch(['部署名称'], $row);
                $positionNameIndex = $this->arraySearch(['役職名称'], $row);
                $contractTypeIndex = $this->arraySearch(['従業員区分'], $row);
                $honmuCompanyNameIndex = $this->arraySearch(['本務会社名称'], $row);
                $honmuBumonGroupCodeIndex = $this->arraySearch(['本務部門グループコード'], $row);
                $honmuBumonGroupNameIndex = $this->arraySearch(['本務部門グループ名称','本務部門グループ正式名称'], $row);
                $honmuBumonCodeIndex = $this->arraySearch(['本務部門コード'], $row);
                $honmuBumonNameIndex = $this->arraySearch(['本務部門名称'], $row);
                $honmuBushoCodeIndex = $this->arraySearch(['本務部署コード'], $row);
                $honmuBushoNameIndex = $this->arraySearch(['本務部署名称'], $row);
                $joinWayIndex = $this->arraySearch(['プロパー／キャリア入社'], $row);
                $occupationIndex = $this->arraySearch(['職種名称','職種'], $row);
                $roleIndex = $this->arraySearch(['ロール'], $row);
                $socialAgeIndex = $this->arraySearch(['社会年齢'], $row);

                $columnGroup = new UserImportColumnGroup(
                    $employeeCodeIndex,
                    $previousEmployeeCodeIndex,
                    $displayNameIndex,
                    $surnameKanjiIndex,
                    $forenameKanjiIndex,
                    $surnameKatakanaIndex,
                    $forenameKatakanaIndex,
                    $surnameAlphabetIndex,
                    $forenameAlphabetIndex,
                    $emailIndex,
                    $bumonGroupCodeIndex,
                    $bumonGroupNameIndex,
                    $bumonCodeIndex,
                    $bumonNameIndex,
                    $bushoCodeIndex,
                    $bushoNameIndex,
                    $positionNameIndex,
                    $contractTypeIndex,
                    $honmuCompanyNameIndex,
                    $honmuBumonGroupCodeIndex,
                    $honmuBumonGroupNameIndex,
                    $honmuBumonCodeIndex,
                    $honmuBumonNameIndex,
                    $honmuBushoCodeIndex,
                    $honmuBushoNameIndex,
                    $joinWayIndex,
                    $occupationIndex,
                    $roleIndex,
                    $socialAgeIndex
                );

            } else {
                if (!is_null($columnGroup->employeeCodeIndex)) {
                    $row[$columnGroup->employeeCodeIndex] = $this->normalizeString($row[$columnGroup->employeeCodeIndex]);
                }
                if (!is_null($columnGroup->previousEmployeeCodeIndex)) {
                    $row[$columnGroup->previousEmployeeCodeIndex] = $this->normalizeString($row[$columnGroup->previousEmployeeCodeIndex]);
                }
                if (!is_null($columnGroup->emailIndex)) {
                    $row[$columnGroup->emailIndex] = $this->normalizeString($row[$columnGroup->emailIndex]);
                }
                if (!is_null($columnGroup->displayNameIndex)) {
                    $row[$columnGroup->displayNameIndex] = $this->normalizeString($row[$columnGroup->displayNameIndex]);
                }
                if (!is_null($columnGroup->surnameKanjiIndex)) {
                    $row[$columnGroup->surnameKanjiIndex] = $this->normalizeString($row[$columnGroup->surnameKanjiIndex]);
                }
                if (!is_null($columnGroup->forenameKanjiIndex)) {
                    $row[$columnGroup->forenameKanjiIndex] = $this->normalizeString($row[$columnGroup->forenameKanjiIndex]);
                }
                if (!is_null($columnGroup->surnameKatakanaIndex)) {
                    $row[$columnGroup->surnameKatakanaIndex] = $this->normalizeString($row[$columnGroup->surnameKatakanaIndex]);
                }
                if (!is_null($columnGroup->forenameKatakanaIndex)) {
                    $row[$columnGroup->forenameKatakanaIndex] = $this->normalizeString($row[$columnGroup->forenameKatakanaIndex]);
                }
                if (!is_null($columnGroup->surnameAlphabetIndex)) {
                    $row[$columnGroup->surnameAlphabetIndex] = $this->normalizeString($row[$columnGroup->surnameAlphabetIndex]);
                }
                if (!is_null($columnGroup->forenameAlphabetIndex)) {
                    $row[$columnGroup->forenameAlphabetIndex] = $this->normalizeString($row[$columnGroup->forenameAlphabetIndex]);
                }
                if (!is_null($columnGroup->bumonGroupCodeIndex)) {
                    $row[$columnGroup->bumonGroupCodeIndex] = $this->normalizeString($row[$columnGroup->bumonGroupCodeIndex]);
                }
                if (!is_null($columnGroup->bumonGroupNameIndex)) {
                    $row[$columnGroup->bumonGroupNameIndex] = $this->normalizeString($row[$columnGroup->bumonGroupNameIndex]);
                }
                if (!is_null($columnGroup->bumonCodeIndex)) {
                    $row[$columnGroup->bumonCodeIndex] = $this->normalizeString($row[$columnGroup->bumonCodeIndex]);
                }
                if (!is_null($columnGroup->bumonNameIndex)) {
                    $row[$columnGroup->bumonNameIndex] = $this->normalizeString($row[$columnGroup->bumonNameIndex]);
                }
                if (!is_null($columnGroup->bushoCodeIndex)) {
                    $row[$columnGroup->bushoCodeIndex] = $this->normalizeString($row[$columnGroup->bushoCodeIndex]);
                }
                if (!is_null($columnGroup->bushoNameIndex)) {
                    $row[$columnGroup->bushoNameIndex] = $this->normalizeString($row[$columnGroup->bushoNameIndex]);
                }
                if (!is_null($columnGroup->positionNameIndex)) {
                    $row[$columnGroup->positionNameIndex] = $this->normalizeString($row[$columnGroup->positionNameIndex]);
                }
                if (!is_null($columnGroup->contractTypeIndex)) {
                    $row[$columnGroup->contractTypeIndex] = $this->normalizeString($row[$columnGroup->contractTypeIndex]);
                }
                if (!is_null($columnGroup->honmuCompanyNameIndex)) {
                    $row[$columnGroup->honmuCompanyNameIndex] = $this->normalizeString($row[$columnGroup->honmuCompanyNameIndex]);
                }
                if (!is_null($columnGroup->honmuBumonGroupCodeIndex)) {
                    $row[$columnGroup->honmuBumonGroupCodeIndex] = $this->normalizeString($row[$columnGroup->honmuBumonGroupCodeIndex]);
                } 
                if (!is_null($columnGroup->honmuBumonGroupNameIndex)) {
                    $row[$columnGroup->honmuBumonGroupNameIndex] = $this->normalizeString($row[$columnGroup->honmuBumonGroupNameIndex]);
                } 
                if (!is_null($columnGroup->honmuBumonCodeIndex)) {
                    $row[$columnGroup->honmuBumonCodeIndex] = $this->normalizeString($row[$columnGroup->honmuBumonCodeIndex]);
                }
                if (!is_null($columnGroup->honmuBumonNameIndex)) {
                    $row[$columnGroup->honmuBumonNameIndex] = $this->normalizeString($row[$columnGroup->honmuBumonNameIndex]);
                }
                if (!is_null($columnGroup->honmuBushoCodeIndex)) {
                    $row[$columnGroup->honmuBushoCodeIndex] = $this->normalizeString($row[$columnGroup->honmuBushoCodeIndex]);
                }
                if (!is_null($columnGroup->honmuBushoNameIndex)) {
                    $row[$columnGroup->honmuBushoNameIndex] = $this->normalizeString($row[$columnGroup->honmuBushoNameIndex]);
                }
                if (!is_null($columnGroup->joinWayIndex)) {
                    $row[$columnGroup->joinWayIndex] = $this->normalizeString($row[$columnGroup->joinWayIndex]);
                }
                if (!is_null($columnGroup->occupationIndex)) {
                    $row[$columnGroup->occupationIndex] = $this->normalizeString($row[$columnGroup->occupationIndex]);
                }
                if (!is_null($columnGroup->roleIndex)) {
                    $row[$columnGroup->roleIndex] = $this->normalizeString($row[$columnGroup->roleIndex]);
                }
                if (!is_null($columnGroup->socialAgeIndex)) {
                    $row[$columnGroup->socialAgeIndex] = $this->normalizeInt($row[$columnGroup->socialAgeIndex]);
                }
                $rows[] = $row;
            }
        }

        fclose($csv);

        return [$rows, $columnGroup];
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
}
