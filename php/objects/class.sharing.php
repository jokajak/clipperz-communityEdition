<?php
/*
	This SQL query will create the table to store your object.

	CREATE TABLE `sharing` (
	`sharingid` int(11) NOT NULL auto_increment,
	`reference` VARCHAR(255) NOT NULL,
	`data` LONGTEXT NOT NULL,
	`version` VARCHAR(255) NOT NULL,
	`creation_date` TIMESTAMP NOT NULL,
	`update_date` TIMESTAMP NOT NULL,
	`access_date` TIMESTAMP NOT NULL, PRIMARY KEY  (`sharingid`)) ENGINE=InnoDB;
*/

/**
* <b>sharing</b> class with integrated CRUD methods.
* @author Php Object Generator
* @version POG 3.0e / PHP5.1 MYSQL
* @see http://www.phpobjectgenerator.com/plog/tutorials/45/pdo-mysql
* @copyright Free for personal & commercial use. (Offered under the BSD license)
* @link http://www.phpobjectgenerator.com/?language=php5.1&wrapper=pdo&pdoDriver=mysql&objectName=sharing&attributeList=array+%28%0A++0+%3D%3E+%27user%27%2C%0A++1+%3D%3E+%27sharingversion%27%2C%0A++2+%3D%3E+%27reference%27%2C%0A++3+%3D%3E+%27data%27%2C%0A++4+%3D%3E+%27version%27%2C%0A++5+%3D%3E+%27creation_date%27%2C%0A++6+%3D%3E+%27update_date%27%2C%0A++7+%3D%3E+%27access_date%27%2C%0A%29&typeList=array%2B%2528%250A%2B%2B0%2B%253D%253E%2B%2527BELONGSTO%2527%252C%250A%2B%2B1%2B%253D%253E%2B%2527HASMANY%2527%252C%250A%2B%2B2%2B%253D%253E%2B%2527VARCHAR%2528255%2529%2527%252C%250A%2B%2B3%2B%253D%253E%2B%2527LONGTEXT%2527%252C%250A%2B%2B4%2B%253D%253E%2B%2527VARCHAR%2528255%2529%2527%252C%250A%2B%2B5%2B%253D%253E%2B%2527TIMESTAMP%2527%252C%250A%2B%2B6%2B%253D%253E%2B%2527TIMESTAMP%2527%252C%250A%2B%2B7%2B%253D%253E%2B%2527TIMESTAMP%2527%252C%250A%2529
*/
include_once('class.pog_base.php');
class sharing extends POG_Base
{
	public $sharingId = '';

	/**
	 * @var VARCHAR(255)
	 */
	public $reference;
	
	/**
	 * @var LONGTEXT
	 */
	public $data;
	
	/**
	 * @var VARCHAR(255)
	 */
	public $version;
	
	/**
	 * @var TIMESTAMP
	 */
	public $creation_date;
	
	/**
	 * @var TIMESTAMP
	 */
	public $update_date;
	
	/**
	 * @var TIMESTAMP
	 */
	public $access_date;
	
	public $pog_attribute_type = array(
		"sharingId" => array('db_attributes' => array("NUMERIC", "INT")),
		"reference" => array('db_attributes' => array("TEXT", "VARCHAR", "255")),
		"data" => array('db_attributes' => array("TEXT", "LONGTEXT")),
		"version" => array('db_attributes' => array("TEXT", "VARCHAR", "255")),
		"creation_date" => array('db_attributes' => array("NUMERIC", "TIMESTAMP")),
		"update_date" => array('db_attributes' => array("NUMERIC", "TIMESTAMP")),
		"access_date" => array('db_attributes' => array("NUMERIC", "TIMESTAMP")),
		);
	public $pog_query;
	
	
	/**
	* Getter for some private attributes
	* @return mixed $attribute
	*/
	public function __get($attribute)
	{
		if (isset($this->{"_".$attribute}))
		{
			return $this->{"_".$attribute};
		}
		else
		{
			return false;
		}
	}
	
	function sharing($reference='', $data='', $version='', $creation_date='', $update_date='', $access_date='')
	{
		$this->reference = $reference;
		$this->data = $data;
		$this->version = $version;
		$this->creation_date = $creation_date;
		$this->update_date = $update_date;
		$this->access_date = $access_date;
	}
	
	
	/**
	* Gets object from database
	* @param integer $sharingId 
	* @return object $sharing
	*/
	function Get($sharingId)
	{
		$connection = Database::Connect();
		$this->pog_query = "select * from `sharing` where `sharingid`='".intval($sharingId)."' LIMIT 1";
		$cursor = Database::Reader($this->pog_query, $connection);
		while ($row = Database::Read($cursor))
		{
			$this->sharingId = $row['sharingid'];
			$this->reference = $this->Unescape($row['reference']);
			$this->data = $this->Unescape($row['data']);
			$this->version = $this->Unescape($row['version']);
			$this->creation_date = $row['creation_date'];
			$this->update_date = $row['update_date'];
			$this->access_date = $row['access_date'];
		}
		return $this;
	}
	
	
	/**
	* Returns a sorted array of objects that match given conditions
	* @param multidimensional array {("field", "comparator", "value"), ("field", "comparator", "value"), ...} 
	* @param string $sortBy 
	* @param boolean $ascending 
	* @param int limit 
	* @return array $sharingList
	*/
	function GetList($fcv_array = array(), $sortBy='', $ascending=true, $limit='')
	{
		$connection = Database::Connect();
		$sqlLimit = ($limit != '' ? "LIMIT $limit" : '');
		$this->pog_query = "select * from `sharing` ";
		$sharingList = Array();
		if (sizeof($fcv_array) > 0)
		{
			$this->pog_query .= " where ";
			for ($i=0, $c=sizeof($fcv_array); $i<$c; $i++)
			{
				if (sizeof($fcv_array[$i]) == 1)
				{
					$this->pog_query .= " ".$fcv_array[$i][0]." ";
					continue;
				}
				else
				{
					if ($i > 0 && sizeof($fcv_array[$i-1]) != 1)
					{
						$this->pog_query .= " AND ";
					}
					if (isset($this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes']) && $this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes'][0] != 'NUMERIC' && $this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes'][0] != 'SET')
					{
						if ($GLOBALS['configuration']['db_encoding'] == 1)
						{
							$value = POG_Base::IsColumn($fcv_array[$i][2]) ? "BASE64_DECODE(".$fcv_array[$i][2].")" : "'".$fcv_array[$i][2]."'";
							$this->pog_query .= "BASE64_DECODE(`".$fcv_array[$i][0]."`) ".$fcv_array[$i][1]." ".$value;
						}
						else
						{
							$value =  POG_Base::IsColumn($fcv_array[$i][2]) ? $fcv_array[$i][2] : "'".$this->Escape($fcv_array[$i][2])."'";
							$this->pog_query .= "`".$fcv_array[$i][0]."` ".$fcv_array[$i][1]." ".$value;
						}
					}
					else
					{
						$value = POG_Base::IsColumn($fcv_array[$i][2]) ? $fcv_array[$i][2] : "'".$fcv_array[$i][2]."'";
						$this->pog_query .= "`".$fcv_array[$i][0]."` ".$fcv_array[$i][1]." ".$value;
					}
				}
			}
		}
		if ($sortBy != '')
		{
			if (isset($this->pog_attribute_type[$sortBy]['db_attributes']) && $this->pog_attribute_type[$sortBy]['db_attributes'][0] != 'NUMERIC' && $this->pog_attribute_type[$sortBy]['db_attributes'][0] != 'SET')
			{
				if ($GLOBALS['configuration']['db_encoding'] == 1)
				{
					$sortBy = "BASE64_DECODE($sortBy) ";
				}
				else
				{
					$sortBy = "$sortBy ";
				}
			}
			else
			{
				$sortBy = "$sortBy ";
			}
		}
		else
		{
			$sortBy = "sharingid";
		}
		$this->pog_query .= " order by ".$sortBy." ".($ascending ? "asc" : "desc")." $sqlLimit";
		$thisObjectName = get_class($this);
		$cursor = Database::Reader($this->pog_query, $connection);
		while ($row = Database::Read($cursor))
		{
			$sharing = new $thisObjectName();
			$sharing->sharingId = $row['sharingid'];
			$sharing->reference = $this->Unescape($row['reference']);
			$sharing->data = $this->Unescape($row['data']);
			$sharing->version = $this->Unescape($row['version']);
			$sharing->creation_date = $row['creation_date'];
			$sharing->update_date = $row['update_date'];
			$sharing->access_date = $row['access_date'];
			$sharingList[] = $sharing;
		}
		return $sharingList;
	}
	
	
	/**
	* Saves the object to the database
	* @return integer $sharingId
	*/
	function Save($deep = true)
	{
		$connection = Database::Connect();
		date_default_timezone_set('America/New_York');
		$this->update_date = date( 'Y-m-d H:i:s');
		$this->access_date = date( 'Y-m-d H:i:s');
		$this->pog_query = "select `sharingid` from `sharing` where `sharingid`='".$this->sharingId."' LIMIT 1";
		$rows = Database::Query($this->pog_query, $connection);
		if ($rows > 0)
		{
			$this->pog_query = "update `sharing` set 
			`reference`='".$this->Escape($this->reference)."', 
			`data`='".$this->Escape($this->data)."', 
			`version`='".$this->Escape($this->version)."', 
			`creation_date`='".$this->creation_date."', 
			`update_date`='".$this->update_date."', 
			`access_date`='".$this->access_date."' where `sharingid`='".$this->sharingId."'";
		}
		else
		{
			$this->pog_query = "insert into `sharing` (`reference`, `data`, `version`, `creation_date`, `update_date`, `access_date` ) values (
			'".$this->Escape($this->reference)."', 
			'".$this->Escape($this->data)."', 
			'".$this->Escape($this->version)."', 
			'".$this->creation_date."', 
			'".$this->update_date."', 
			'".$this->access_date."' )";
		}
		$insertId = Database::InsertOrUpdate($this->pog_query, $connection);
		if ($this->sharingId == "")
		{
			$this->sharingId = $insertId;
		}
		return $this->sharingId;
	}
	
	
	/**
	* Clones the object and saves it to the database
	* @return integer $sharingId
	*/
	function SaveNew($deep = false)
	{
		// set the default timezone so date doesn't complain later
		// could have some weirdness if users are in different timezones, but meh
		date_default_timezone_set('America/New_York');
		$this->sharingId = '';
		$this->creation_date = date( 'Y-m-d H:i:s');
		return $this->Save($deep);
	}
	
	
	/**
	* Deletes the object from the database
	* @return boolean
	*/
	function Delete($deep = false, $across = false)
	{
		$connection = Database::Connect();
		$this->pog_query = "delete from `sharing` where `sharingid`='".$this->sharingId."'";
		return Database::NonQuery($this->pog_query, $connection);
	}
	
	
	/**
	* Deletes a list of objects that match given conditions
	* @param multidimensional array {("field", "comparator", "value"), ("field", "comparator", "value"), ...} 
	* @param bool $deep 
	* @return 
	*/
	function DeleteList($fcv_array, $deep = false, $across = false)
	{
		if (sizeof($fcv_array) > 0)
		{
			if ($deep || $across)
			{
				$objectList = $this->GetList($fcv_array);
				foreach ($objectList as $object)
				{
					$object->Delete($deep, $across);
				}
			}
			else
			{
				$connection = Database::Connect();
				$pog_query = "delete from `sharing` where ";
				for ($i=0, $c=sizeof($fcv_array); $i<$c; $i++)
				{
					if (sizeof($fcv_array[$i]) == 1)
					{
						$pog_query .= " ".$fcv_array[$i][0]." ";
						continue;
					}
					else
					{
						if ($i > 0 && sizeof($fcv_array[$i-1]) !== 1)
						{
							$pog_query .= " AND ";
						}
						if (isset($this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes']) && $this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes'][0] != 'NUMERIC' && $this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes'][0] != 'SET')
						{
							$pog_query .= "`".$fcv_array[$i][0]."` ".$fcv_array[$i][1]." '".$this->Escape($fcv_array[$i][2])."'";
						}
						else
						{
							$pog_query .= "`".$fcv_array[$i][0]."` ".$fcv_array[$i][1]." '".$fcv_array[$i][2]."'";
						}
					}
				}
				return Database::NonQuery($pog_query, $connection);
			}
		}
	}
	
}
?>
