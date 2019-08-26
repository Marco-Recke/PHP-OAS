<?php

require_once(dirname(__FILE__).'/jsonformat.php');
require_once(dirname(__FILE__).'/csvformat.php');
require_once(dirname(__FILE__).'/xmlformat.php');
require_once(dirname(__FILE__).'/counterxmlformat.php');
require_once(dirname(__FILE__).'/excelxmlformat.php');
require_once(dirname(__FILE__).'/graphformat.php');
require_once(dirname(__FILE__).'/graphformat2.php');


class FormatNotSupported extends Exception{}

/**
 * Factory class to coordinate the different format classes
 */
class FormatFactory
{
    /**
     * Creates and returns a new format instance depending on content type
     *
     * @param $contentType  a content type
     * @return a new instance
     */
    public static function createFormatObject($paramInstance,$dataObject) {
        switch($paramInstance->getValue('format')) {
            case "json":
                return new JsonFormat($paramInstance,$dataObject);

            case "csv":
                return new CsvFormat($paramInstance,$dataObject);

            case "xml":
                return new XmlFormat($paramInstance,$dataObject);
                // return new CounterXmlFormat($paramInstance,$dataObject);

            case "excel":
                // TODO: unterschiedliche Excel zurückgeben (müssen noch erstellt werden)
                // Code dafür:
                // switch ($dataObject->getReportName()) {
                //     case 'JR1':
                //         return new CounterExcelJR1Format($dataObject);
                //         break;
                //     case 'BR1':
                //         return new CounterExcelBR1Format($dataObject);
                //         break;
                //     default:
                //         throw new FormatNotSupported('Content type not implemented');
                //         break;
                // }
                return new ExcelXmlFormat($paramInstance,$dataObject);

//            case "graph":
//                return new GraphFormat($dataObject);
//
//            case "graph2":
//                return new GraphFormat2($dataObject);

            default:
                throw new FormatNotSupported('Content type not implemented');
        }
    }
}
