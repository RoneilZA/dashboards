<?PHP
	require_once(BASE."basefunctions/baseapis/man_exception.class.php");
	require_once(BASE."basefunctions/baseapis/Table.class.php");
	
	/** class::EquipFaults
	* @author Justin Ward
	* @author justinw@manlinegroup.com
	* @copyright 2009 onwards Manline (Pty) Ltd
	*/
	
	require_once(BASE."/basefunctions/baseapis/man_exception.class.php");
	require_once(BASE."/basefunctions/baseapis/Table.class.php");
	
	class EquipFaults extends Table {
		// Variables {
			protected $_cols = array();
			protected $_dependantTables = array(
				"drivers"	=>array("name"=>"drivers", "title"=>"driver", "on"=>"equipfault.driverid=driver.id"),
				"users"		=>array("name"=>"users", "title"=>"user", "on"=>"equipfault.assignedid=user.personid")
				);
			protected $_children = array(
				"equip_notes"=>array("table"=>"equip_notes", "cols"=>"*", "customkey"=>"faultid")
				);
			protected $_title	= "equipfault";
			protected $_name	= "equip_faults";
			protected $_faultid;
		// }
		
		public function cascadeDelete($id) {
			print("TEST");
		}
		
		// Public functions {
			/** EquipFaults::__construct()
			* class constructor
			* @see Table::__construct()
			* @return null
			*/
			public function __construct() {parent::__construct($this->_name);}
			
			public function __destruct() {unset($this);}
		// }
		
		// Details of what will populate into result sets {
			/** EquipFaults::report($options)
			* @param none
			* @return array data on success false otherwise
			*/
			public function report() {
				$query	= "SHOW COLUMNS FROM ".$this->_name;
				$result	= sqlQuery($query);
				
				if($result) {
					foreach ($result as $colkey=>$colval) {
						$data[$this->_title][]	= $colval["Field"];
					}
					
					foreach ($this->_dependantTables as $depkey=>$depval) {
						$depquery		= "SHOW COLUMNS FROM ".$depval["name"];
						$depresult	= sqlQuery($depquery);
						
						if($depresult) {
							foreach ($depresult as $colkey=>$colval) {
								$data[$depval["title"]][]	= $colval["Field"];
							}
						}
					}
				}
				
				return $data;
			}
		// }
		
		// Setters {
			public function setEquipFaultId($faultid) {
				$this->_faultid	= $faultid;
			}
		// }
		
		// Getters {
			// Get one record from Equip Faults {
				/** EquipFaults::getRow($options)
				* @param $options array
				* @param $options["where"] string standard sql where statement
				* @return array data
				*/
				public function getRow(array $options) {
					$options["table"]		= $this->_name." as ".$this->_title;
					$options["onerow"]	= 1;
					
					if(!$options["select"]) {
						$collist	= $this->report();
						
						$select		=	"";
						foreach ($collist as $tablekey=>$tableval) {
							foreach ($tableval as $colkey=>$colval) {
								if(strlen($select) > 0) {
									$select	.= ", ";
								}
								if($colval == "id") {
									$select	.= $tablekey.".".$colval." as ".$tablekey.$colval;
								} else {
									$select	.= $tablekey.".".$colval;
								}
							}
						}
						
						$options["select"]	= $select;
					}
					
					foreach ($this->_dependantTables as $depkey=>$depval) {
						$name		= $depval["name"];
						$title	= $depval["title"];
						if($depval["id"]) {
							$options["select"]	.= ", ".$depval["id"];
						}
						$options["table"] .= " left join ".$name." as ".$title." on ".$depval["on"];
					}
					
					$data = sqlPull($options);
					
					if ($options["children"]) {
						foreach ($this->_children as $childkey=>$childval) {
							$child = (array)array();
							$child["select"] = is_array($childval["cols"]) ? implode(",", $childval["cols"]) : $childval["cols"];
							$child["table"] = $childval["table"];
							$child["where"] = "faultid=".$data["equipfaultid"];
							
							$data[$childval["table"]] = sqlPull($child);
						}
					}
					return $data;
				}
			// }
		
			// Get a series of records from Equip_Faults {
				/** EquipFaults::getRowSet($options)
				* @param $options array
				* @return array data on success false otherwise
				*/
				public function getRowSet(array $options = array()) {
					$options["table"]		= $this->_name." as ".$this->_title;
					
					foreach ($this->_dependantTables as $depkey=>$depval) {
						$name		= $depval["name"];
						$title	= $depval["title"];
						
						$options["table"] .= " LEFT JOIN ".$name." AS ".$title." ON ".$depval["on"];
					}
					
					$data = sqlPull($options);
					
					if (($options["children"] == 1) && ($data)) {
						foreach ($data as $id=>$row) {
							foreach ($this->_children as $key=>$val) {
								$child = (array)array();
								$child["select"] = is_array($val["cols"]) ? implode(",", $val["cols"]) : $val["cols"];
								$child["table"] = $val["table"];
								$child["where"] = "faultid=".$id;
								
								$children = sqlPull($child);
								
								if($children) {
									$data[$id][$val["table"]] = $children;
								}
							}
						}
					}
					return $data;
				}
			// }
		
			// Get actions taken against driver regarding a fault {
				/** EquipFaults::getDriverAction($options)
				* @param $options array
				* @return array data
				* Users the protected variable _faultid, which must be set beforehand.
				*/
				public function getDriverAction() {
					$data = sqlPull(array("table"=>"driver_actions", "where"=>"source=1 AND sourceid=".$this->_faultid, "onerow"=>1));
					
					return $data;
				}
			// }
		// }
		
		/** EquipFaults::setVariables($config)
		* @param $config array
		* @param $config["children"] = array chilren in format array("table"=>"pick_my_socks_up", "cols"=>"*" | array("field1", "field2"))
		* @param $config["dependants"] = array dependants in format array(0=>array("table"=>array("alias"=>"tablename"), "on"=>string standard sql on, "cols"=>array("field", "field", "field")))
		* @return null
		*/
		public function setVariables(array $config = array()) {
			if (isset($config["children"])) {$this->_children = $config["children"];}
			if (isset($config["dependants"])) {$this->_dependantTables = $config["dependants"];}
			return null;
		}
	
	//: Private functions
	}
?>
