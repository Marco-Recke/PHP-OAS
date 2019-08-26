<?php
require_once(dirname(__FILE__).'/dataformat.php');

/**
 * XML Export Class
 *
 * @package output
 * @version 0.1
 */
class XmlFormat extends DataFormat
{
	const XMLNS_OAS 		= 'http://www.gbv.de/schemas/oas';
	const XMLSCHEMALOC_OAS 	= 'http://oase.gbv.de/schemas/oas.xsd';
	const XMLNS_SCHEMA 		= 'http://www.w3.org/2001/XMLSchema-instance';

	function __construct($paramInstance,$dataObject)
	{
		parent::__construct($paramInstance,$dataObject);
		$this->contentType = 'xml';
	}

	/**
	 * Creates XML data depending on the data object
	 *
	 * @param $dataObject  the object holding the data
	 */
	public function formatData()
	{
		$writer = new XMLWriter();


		$writer->openMemory();
		$writer->startDocument('1.0');

		$writer->setIndent(4);

		// in root element 'report' the default namespace is defined
		$writer->startElementNS(NULL,'report',self::XMLNS_OAS);
		// $writer->startElement('report');
		$writer->writeAttributeNS('xsi','schemaLocation',self::XMLNS_SCHEMA,self::XMLNS_OAS.' '.self::XMLSCHEMALOC_OAS);
		$writer->writeAttribute('name', 'basic');
		$writer->writeAttribute('created', gmdate('c'));

		$writer->writeElement('from', $this->dataObject->getFrom());
		$writer->writeElement('until', $this->dataObject->getUntil());
		$writer->writeElement('granularity', $this->dataObject->getGranularity());

		$writer->startElement('classifications');
		$classifications = $this->dataObject->getClassifications();
		foreach ($classifications as $classification) {
			$writer->writeElement('classification', $classification);
		}
		$writer->endElement(); // classifications

		$writer->startElement('entries');
		//----------------------------------------------------
		foreach ($this->dataObject->getData() as $dataset) {
			$writer->startElement('entry');
			$writer->writeElement('identifier', $dataset['identifier']);
			$writer->writeElement('date', $dataset['date']);
			foreach ($this->dataObject->getTypes() as $type) {
				$writer->startElement('access');
				$writer->writeElement('type', $type);
				$writer->writeElement('count', $dataset[$type]);
				$writer->endElement(); // access
			}
			$writer->endElement(); // entry
		}
		//----------------------------------------------------
		$writer->endElement(); // entries

		$writer->endElement(); // report

		$writer->endDocument();

		$this->formattedData = $writer->flush();
	}
}

?>