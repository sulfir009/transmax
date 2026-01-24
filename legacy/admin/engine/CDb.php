<?php
class CDb
{

    var $db;
    function __construct($db) {
        $this->db = $db;
    }

	public function query($query){
		return mysqli_query($this->db, $query, MYSQLI_STORE_RESULT);
	}

    public function setlang($lang)
    {
        $this->lang = $lang;
    }

	public function getAll($query, $params = array())
	{
		$result = array();
		$getResult = mysqli_query($this->db, $query, MYSQLI_STORE_RESULT);
		while ($row = mysqli_fetch_assoc($getResult)) {
			$result[]=$row;
		}
		return $result;
	}

	public function getOne($query)
	{
		$result = mysqli_query($this->db, $query.' LIMIT 1', MYSQLI_STORE_RESULT);
		if( mysqli_num_rows($result) > 0 ){
			return mysqli_fetch_assoc($result);
		}else{
			return NULL;
		}
	}
}
