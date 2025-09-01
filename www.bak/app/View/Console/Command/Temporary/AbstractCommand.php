<?php

namespace App\View\Console\Command\Temporary;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use App\View\Console\Command\AbstractCommand as ParentAbstractCommand;
use App\Infrastructure\Dao\DownloadableDao;

class AbstractCommand extends ParentAbstractCommand
{
    /**
     * @access protected
     * @param string $content
     * @return resource
     */
    protected function openXlsxAsCsv(string $filepath)
    {
        $objSpreadsheet = IOFactory::load($filepath);// Excelファイルの読み込み
        $objWriter = new Csv($objSpreadsheet);
        $objWriter->setDelimiter(',');
        $objWriter->setEnclosure('"');
        $objWriter->setLineEnding("\r\n");
        $objWriter->setUseBOM(false); // falseでBOMを無効にする。 ＊そもそも必要ないかもしれない
        $objWriter->setOutputEncoding('UTF-8');// 文字化けを防ぐため、文字コード「SJIS-WIN」を指定する
        $objWriter->setSheetIndex(0);

        $tmpCsv = tmpfile();
        $tmpCsvUri = stream_get_meta_data($tmpCsv)['uri'];
        $objWriter->save($tmpCsvUri);

        rewind($tmpCsv);

        return $tmpCsv;
    }

    /**
     * @param string $fileName
     * @param string[] $headerRow
     * @param string[][] $rows
     *
     * @return void
     */
    protected function saveRowsToCsv(string $fileName, array $headerRow, array $rows)
    {
        $fp = fopen('php://memory', 'w');

        fputcsv($fp, $headerRow);

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        rewind($fp);
        $content = stream_get_contents($fp);

        fclose($fp);

        $content = mb_convert_encoding($content, 'sjis', 'utf-8');

        DownloadableDao::create([
            'name' => $fileName,
            'mimeType' => 'text/csv',
            'content' => $content,
        ]);
    }

    /**
     * @param string[] $searches
     * @param string[] $haystack
     * @return ?int
     */
    protected function arraySearch(array $searches, array $haystack): ?int
    {
        foreach ($searches as $search) {
            if (($i = array_search($search, $haystack, TRUE)) !== FALSE) {
                return $i;
            }
        }
        return NULL;
    }
}
