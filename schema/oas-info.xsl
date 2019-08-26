<?xml version="1.0" encoding="UTF-8" ?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"
    targetNamespace="http://dini.de/namespace/oas-info"
    elementFormDefault="qualified"
    attributeFormDefault="unqualified">
    
	<xs:annotation>
		<xs:documentation>
		    XML Schema defining the XML metadata format for usage event metadata, 
		    specifically for purposes within a larger client/server architecture 
		    for harvesting usage information. This specific metadata is only a subset
		    of the whole metadata that describes a usage event. The main data
		    structure is an OpenURL ContextObject.
		</xs:documentation>
		<xs:appinfo xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title>XML Format oas-info</dc:title>
			<dc:creator>Hans-Werner Hilse</dc:creator>
			<dc:creator>OA-Statistik Project (Germany)</dc:creator>
			<dc:description>
			    This XML Schema defines a format to express usage event 
			    specific metadata as an XML document.
			</dc:description>
			<dc:identifier>http://dini.de/namespace/oas-info</dc:identifier>
			<dcterms:created>2009-10-12</dcterms:created>
		</xs:appinfo>
	</xs:annotation>
	
	<xs:element name="oa-statistics">
		<xs:complexType>
			<xs:sequence>
				<xs:element name="status_code">
					<xs:annotation>
						<xs:documentation>
						    specifies the HTTP status code for the HTTP query underlying the usage event
						</xs:documentation>
					</xs:annotation>
					<xs:simpleType>
						<xs:restriction base="xs:integer">
							<xs:pattern value="[0-9][0-9][0-9]"/>
						</xs:restriction>
					</xs:simpleType>
				</xs:element>
				<xs:element name="size" type="xs:nonNegativeInteger">
					<xs:annotation>
						<xs:documentation>
						    specifies the transmitted amount of data
						</xs:documentation>
					</xs:annotation>
				</xs:element>
				<xs:element name="document_size" type="xs:nonNegativeInteger">
					<xs:annotation>
						<xs:documentation>
						    specifies the full size of the document of which a part is being transmitted
						</xs:documentation>
					</xs:annotation>
				</xs:element>
				<xs:element name="format">
					<xs:annotation>
						<xs:documentation>
						    specifies the MIME type of the data transmitted
						</xs:documentation>
					</xs:annotation>
					<xs:simpleType>
					    <xs:restriction base="xs:string">
						    <xs:pattern value="\c+/\c+"/>
					    </xs:restriction>
					</xs:simpleType>
				</xs:element>
				<xs:element name="service" type="xs:anyURI">
					<xs:annotation>
						<xs:documentation>
						    an URI identifier of the service that handled the usage event, e.g. the document server
						</xs:documentation>
					</xs:annotation>
				</xs:element>
			</xs:sequence>
		</xs:complexType>
	</xs:element>

</xs:schema>
