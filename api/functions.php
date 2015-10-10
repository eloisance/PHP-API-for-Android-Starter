<?php 

function varAreOk($params) {
	foreach ($params as $param) {
		if(!isset($param) || $param == '' || $param == null) {
			return false;
		}
	}
	return true;
}

?>