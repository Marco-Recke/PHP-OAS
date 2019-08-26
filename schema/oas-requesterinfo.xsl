<?xml version="1.0" encoding="UTF-8" ?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"
    targetNamespace="http://dini.de/namespace/oas-requesterinfo"
    elementFormDefault="qualified"
    attributeFormDefault="unqualified">
    
	<xs:annotation>
		<xs:documentation>
		    XML Schema defining metadata for "Requester" metadata within the
		    context of an OpenURL ContextObject. It was designed to express more
		    information on a user of electronic resources but with careful consideration
		    of the user's privacy.
		</xs:documentation>
		<xs:appinfo xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">
			<dc:title>XML Format oas-requesterinfo</dc:title>
			<dc:creator>Hans-Werner Hilse</dc:creator>
			<dc:creator>OA-Statistik Project (Germany)</dc:creator>
			<dc:description>
			    XML Schema defining metadata for "Requester" metadata within the
			    context of an OpenURL ContextObject. It was designed to express more
			    information on a user of electronic resources but with careful consideration
			    of the user's privacy.
			</dc:description>
			<dc:identifier>http://dini.de/namespace/oas-requesterinfo</dc:identifier>
			<dcterms:created>2009-10-12</dcterms:created>
		</xs:appinfo>
	</xs:annotation>
	
	<xs:element name="requesterinfo">
		<xs:complexType>
			<xs:sequence>
				<xs:element name="hashed-ip" type="xs:hexBinary">
					<xs:annotation>
						<xs:documentation>
						    the IP address from which the usage event originates, made anonymous by using a salted hash function on the address string
						</xs:documentation>
					</xs:annotation>
				</xs:element>
				<xs:element name="hashed-c" type="xs:hexBinary">
					<xs:annotation>
						<xs:documentation>
						    the class-C network part of the IP address, made anonymous by the same means as for the IP address
						</xs:documentation>
					</xs:annotation>
				</xs:element>
				<xs:element name="hostname" type="xs:string" minOccurs="0">
					<xs:annotation>
						<xs:documentation>
						    the client's host name, chopped to the second-level domain part. This element is to be omitted if there is no hostname for the client.
						</xs:documentation>
					</xs:annotation>
				</xs:element>
				<xs:element name="classification" minOccurs="0">
					<xs:annotation>
						<xs:documentation>
						    further information about the class of the user which can only be determined locally at the service
						</xs:documentation>
					</xs:annotation>
					<xs:simpleType>
						<xs:list>
							<xs:simpleType>
								<xs:restriction base="xs:string">
									<xs:enumeration value="internal">
										<xs:annotation>
											<xs:documentation>
											    Usage events that happen just due to internal system reasons, e.g. automated integrity checks, high availability checks etc.
											</xs:documentation>
										</xs:annotation>
									</xs:enumeration>
									<xs:enumeration value="administrative">
										<xs:annotation>
											<xs:documentation>
											    Usage events that happen due to administrative decisions, e.g. for quality assurance.
											</xs:documentation>
										</xs:annotation>
									</xs:enumeration>
									<xs:enumeration value="institutional">
										<xs:annotation>
											<xs:documentation>
											    Usage events triggered from within the institution running the service for which usage events are collected.
											</xs:documentation>
										</xs:annotation>
									</xs:enumeration>
								</xs:restriction>
							</xs:simpleType>
						</xs:list>
					</xs:simpleType>
				</xs:element>
				<xs:element name="hashed-session" type="xs:hexBinary" minOccurs="0">
					<xs:annotation>
						<xs:documentation>
						    optionally (if available/in use) specifies a session id or hash of it for the full usage session of the given user
						</xs:documentation>
					</xs:annotation>
				</xs:element>
				<xs:element name="user-agent" type="xs:string" minOccurs="0">
					<xs:annotation>
						<xs:documentation>
						    specifies the full HTTP user agent string if given
						</xs:documentation>
					</xs:annotation>
				</xs:element>
			</xs:sequence>
		</xs:complexType>
	</xs:element>

</xs:schema>
