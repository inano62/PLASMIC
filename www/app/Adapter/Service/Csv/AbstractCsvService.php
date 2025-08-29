<?php

namespace App\Adapter\Service\Csv;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

abstract class AbstractCsvService
{
    /**
     * @access protected
     * @param string $content
     * @return resource
     */
    protected function convertContentToResource(string $content)
    {
        $finfo  = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $content);
        finfo_close($finfo);

        switch ($mimeType) {

        case 'text/csv':
            return $this->convertContentCsvToResource($content);

        case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            return $this->convertContentXlsxToResource($content);

        default:
            return $this->convertContentCsvToResource($content);
        }

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
     * @param string $content
     * @return resource
     */
    protected function convertContentCsvToResource(string $content)
    {
        $encode = mb_detect_encoding($content, ['UTF-8', 'SJIS-win']);
        if ($encode !== 'UTF-8') { $content =  mb_convert_encoding($content, 'UTF-8', $encode);}

        $resource = tmpfile();
        fwrite($resource, $content);
        rewind($resource);
        return $resource;
    }

    /**
     * @access protected
     * @param string $content
     * @return resource
     */
    protected function convertContentXlsxToResource(string $content)
    {
        $tmpXlsx = tmpfile();
        fwrite($tmpXlsx, $content);
        rewind($tmpXlsx);
        $tmpXlsxUri = stream_get_meta_data($tmpXlsx)['uri'];

        $objSpreadsheet = IOFactory::load($tmpXlsxUri);// Excelファイルの読み込み
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

        fclose($tmpXlsx);

        return $tmpCsv;
    }
}
