<?php
class UsageDataModel {
	
	private $filter = null;
	public function __construct() {
		global $config; //TODO
		$this->db = new PDO('mysql:host=' . $config->db_host . ';dbname=' . $config->db_schema, $config->db_user, $config->db_password);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	}

	/**
	 * Gets usage data for a document in a certain timeframe
	 * @param $identifier document identifier
	 * @param $format html, csv or xml
	 * @param $startDate
	 * @param $endDate
	 * @return unknown_type
	 */
	public function getUsageData($identifier, $format = 'html', DateTime $startDate = null, DateTime $endDate = null) {
		global $config;
		
		// Allowed values for $format: html or csv
		$format = strtolower($format);
		
		if ($startDate == null) $startDate = new DateTime('2000-01-01 00:00:00', new DateTimeZone('UTC')); //TODO
		if ($endDate == null) $endDate = new DateTime('now', new DateTimeZone('UTC'));

		$q_id=$this->db->prepare($q="select id from {$config->db_tp}Identifier where identifier like ?");
		$q_id->execute(array($identifier));
		$identifier_id=$q_id->fetchColumn(0);
		$q_id->closeCursor();

		$q_st=$this->db->prepare("select id from {$config->db_tp}ServiceType where servicetype like ?");
		$q_st->execute(array('fulltext'));
		$servicetype_fulltext=$q_st->fetchColumn(0);
		$q_st->closeCursor();
		$q_st->execute(array('ALL'));
		$servicetype_ALL=$q_st->fetchColumn(0);
		$q_st->closeCursor();

		$query="
			select
				distinct date,
				(select ifnull(sum(counter),0) from {$config->db_tp}UsageData
					where identifierid=U.identifierid and date=U.date
					and servicetypeid=$servicetype_fulltext
				) as counter,
				(select ifnull(sum(logec),0) from {$config->db_tp}UsageData
					where identifierid=U.identifierid and date=U.date
					and servicetypeid=$servicetype_ALL
				) as logec,
				(select ifnull(sum(ifabc),0) from {$config->db_tp}UsageData
					where identifierid=U.identifierid and date=U.date
					and servicetypeid=$servicetype_ALL
				) as ifabc,
				(select ifnull(sum(robots),0) from {$config->db_tp}UsageData
					where identifierid=U.identifierid and date=U.date
					and servicetypeid=$servicetype_ALL
				) as robots
			from {$config->db_tp}UsageData as U
			where identifierid=:identifierid and date between :startdate and :enddate order by date";

		$statement = $this->db->prepare($query);
		$statement->bindParam(':identifierid', $identifier_id, PDO::PARAM_STR);
		$statement->bindParam(':startdate', $startDate->format('Y-m-d'), PDO::PARAM_STR);
		$statement->bindParam(':enddate', $endDate->format('Y-m-d'), PDO::PARAM_STR);
		$statement->execute();
		
		$count = 0;
		$return = '';
		if ($format == 'html') {
			$return .= '<table id="data"><thead><tr><th>Datum</th><th>COUNTER</th><th>IFABC</th></tr></thead><tbody>';
			while ($current = $statement->fetchObject()) {
				$count++;
				$return .= '<tr><th>' . $current->date . '</th><td class="counter">'
						. $current->counter . '</td><td class="ifabc">' . $current->ifabc . '</td></tr>';
			}
			$return .= '</tbody></table>';
		} else if ($format == 'csv') {
			$return .= "Datum;COUNTER;IFABC\n";
			while ($current = $statement->fetchObject()) {
				$count++;
				$return .= "$current->date;$current->counter;$current->ifabc\n";
			}
		} else if ($format == 'xml') {
			$return .= "<hitrates identifier=\"$identifier\" granularity=\"day\">";
			while ($current = $statement->fetchObject()) {
				$count++;
				$return .= "<hitrate date=\"$current->date\">";
				$return .= "<count standard=\"COUNTER\">$current->counter</count>";
				$return .= "<count standard=\"IFABC\">$current->ifabc</count>";
				$return .= "</hitrate>";
			}
			if ($count == 0) $return .= "<nodata/>";
			$return .= "</hitrates>";
		}
		
		return array('text' => $return, 'count' => $count);
		
	}
	/**
	 * Returns a PDOStatement containing all known robots along with some additional data
	 * (not needed for checking against blacklist, use getBlacklist instead)
	 * @return PDOStatement
	 */
	public function getRobots() {
		$query = "SELECT r.name name, useragent, s.name source, r.comment, updated
				FROM Robots r LEFT OUTER JOIN RobotsSource s ON r.source = s.id
				ORDER BY r.name";
		return $this->db->query($query);
	}
	
	/**
	 * Returns a list of useragents known to be robots
	 * @return array
	 */
	public function getBlacklist() {
		return $this->db->query("SELECT useragent FROM Robots")->fetchAll();
	}
}
