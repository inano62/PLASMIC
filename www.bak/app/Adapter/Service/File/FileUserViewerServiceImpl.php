<?php

namespace App\Adapter\Service\File;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Application\Service\File\FileUserViewerService;
use App\Domain\Object\UserViewer\UserViewerForImport;

class FileUserViewerServiceImpl implements FileUserViewerService
{
    /**
     * @param UploadedFile $file
     * @return ?\App\Domain\Object\UserViewer\UserViewerForImport[]
     */
    public function readForImport(UploadedFile $file): ?array
    {
        $csv = $this->openShiftFile($file);

        $honmuBumonCodeIndex = NULL;
        $employeeCodeIndexes = [];

        $userViewers = [];

        while ($row = fgetcsv($csv)) {
            if (is_null($honmuBumonCodeIndex)) {
                $honmuBumonCodeIndex = array_search('本務部門コード', $row, TRUE);

                foreach (array_keys($row) as $i) {
                    if (Str::startsWith($row[$i], 'ユーザID')) {
                        $employeeCodeIndexes[] = $i;
                    }
                }

                if ($honmuBumonCodeIndex === FALSE || !$employeeCodeIndexes) { return NULL; }

            } else {

                if (is_null($row[$honmuBumonCodeIndex]) || $row[$honmuBumonCodeIndex] === '') { continue; }

                foreach ($employeeCodeIndexes as $employeeCodeIndex) {
                    if (!is_null($row[$employeeCodeIndex]) && $row[$employeeCodeIndex] !== '') {
                        $userViewers[] = new UserViewerForImport(
                            $row[$employeeCodeIndex],
                            $row[$honmuBumonCodeIndex]
                        );
                    }
                }
            }
        }

        fclose($csv);

        return $this->dedupeUserViewers($userViewers);
    }

    /**
     * @access private
     * @param \App\Domain\Object\UserViewer\UserViewerForImport[] $userViewers
     * @return \App\Domain\Object\UserViewer\UserViewerForImport[]
     */
    private function dedupeUserViewers(array $userViewers)
    {
        $codeToUserViewer = array_reduce($userViewers, function($carry, $userViewer) {
            $carry[
                $userViewer->honmuBumonCode . '.' . $userViewer->userEmployeeCode
            ] = $userViewer;

            return $carry;
        }, []);

        return array_values($codeToUserViewer);
    }

    /**
     * @access private
     * @param UploadedFile $file
     * @return resource
     */
    private function openShiftFile(UploadedFile $file)
    {
        $sjisContent = $file->get();
        $utf8Content = mb_convert_encoding($sjisContent, 'UTF-8', 'SJIS-win');

        $csv = tmpfile();
        fwrite($csv, $utf8Content);
        rewind($csv);

        return $csv;
    }
}
