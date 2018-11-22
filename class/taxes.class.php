<?php
/*
 *  This file is part of AutoMicroEntreprise Module, a module for Dolibarr.
 *  Copyright (C) 2013-2018 Fabrice Delliaux <netbox253@gmail.com>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, version 3 of the License.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 *	\file		automicroent/class/taxes.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Implements taxes manager object
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */

require_once __DIR__.'/exceptions.class.php';
//require_once __DIR__.'/enum.class.php';

// TODO
// TODO quarter support
// TODO

class taxesManager {

	private $taxes = array();
	private $dbt = ''; // db table

	function __construct() {
		$this->dbt = MAIN_DB_PREFIX.'ame_rates';
	}

	function __destruct() {
	}

	/*
	 *	Public methods
	 *
	 */

	/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

	public function getTaxYear($i=0) {
		return $this->taxes[$i]->year;
	}

	public function getTaxName($i=0) {
		return $this->taxes[$i]->name;
	}

	public function getTaxComment($i=0) {
		return $this->taxes[$i]->comment;
	}

	public function getTaxLevel($i=0) {
		return $this->taxes[$i]->level;
	}

	public function getMatchArray($year, $level=NULL) {
		$a = array();
		$b = array();
		foreach($this->taxes as $i => $o) {
			if($o->year === $year) {
				$b[] = $i;
				if($o->level === $level)
					$a[] = $i;
			}
		}
		if( is_null($level) )
			return $b;
		return $a;
	}

	public function getTaxType($i=0) {
		return $this->taxes[$i]->type;
	}

	public function getTaxStringType($i=0) {
		global $langs;
		$ref = '';
		switch( $this->taxes[$i]->type ) {
			case 0:
				$ref = 'AME_Products';
				break;
			case 1:
				$ref = 'AME_Services';
				break;
			case 2:
				$ref = 'AME_ProductsAndServices';
				break;
			default:
				$ref = 'AME_UnknownType';
				break;
		}
		return $langs->trans($ref);
	}

	public function getTaxRate($i=0) {
		return $this->getTaxStringFromIndex( $i );
	}

	public function getTaxAccuracy($i=0) {
		return $this->taxes[$i]->accuracy;
	}

	public function getTaxFirst($i=0) {
		return $this->taxes[$i]->first;
	}

	public function getTaxSecond($i=0) {
		return $this->taxes[$i]->second;
	}

/*
	// XXX : rounded values at taxes level ?

	public function getTaxRound($i=0) {
		return $this->taxes[$i]->round;
	}

	public function getRoundedValue($value, $i=0) {
		$ret = NULL;
		$r = $this->getTaxRound($i);
		switch( $r ) {
			case AME_ENUM::TAXROUND_NONE:
				$ret = $value;
				break;
			case AME_ENUM::TAXROUND_FLOOR:
				$ret = floor($value);
				break;
			case AME_ENUM::TAXROUND_ROUND:
				// TODO check PHP_ROUND_HALF_DOWN
				$ret = round($value, 0, PHP_ROUND_HALF_DOWN);
				break;
			case AME_ENUM::TAXROUND_CEIL:
				$ret = ceil($value);
				break;
			default :
				throw new AMEUnknownENUM( $r );
				break;
		}
		return $ret;
	}
*/
	public function getTaxID($i=0) {
		return $this->taxes[$i]->rowid;
	}

	// returns array containing unique years recorded in database
	public function getYearsArray() {
		$a = array();
		foreach($this->taxes as $o) {
			if( ! in_array( $o->year, $a, true ) )
				$a[] = $o->year;
		}
		return $a;
	}

	/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

	public function insertTax($year, $level, $type, $name='AME_UPDATE',
				 $comment=NULL, $accuracy=2, $first=0, $second=0,
				 $q=0) {
				// XXX : rounded values at taxes level ?
				//   $round=AME_ENUM::TAXROUND_FLOOR, $q=0 ) {
		global $db;

		$name = $db->escape($name);
		$comment = $db->escape($comment);
		$comment = is_null($comment) ? 'NULL' : "'$comment'";

		$sql = "insert into $this->dbt (name,comment,level,year,quarter,type,accuracy,first,second)";
		$sql .= " values ('$name', $comment, $level, $year, $q, $type, $accuracy, $first, $second)";
		// XXX : rounded values at taxes level ?
		//$sql = "insert into $this->dbt (name,comment,level,year,quarter,type,first,second,round)";
		//$sql .= " values ('$name', $comment, $level, $year, $q, $type, $first, $second, $round)";

		/* exec */
		$result = $db->query( $sql );
		if( $result === FALSE )
			throw new DBQueryError( $db->lasterror() );
	}

	public function deleteTaxes($sup_id) {
		global $db;
		if( sizeof($sup_id) > 0 ) {
			$sql = "delete from $this->dbt where rowid IN (";
			$i = 0;
			foreach( $sup_id as $id ) {
				if($i > 0) $sql .= ', ';
				$sql .= "$id";
				$i++;
			}
			$sql .= ')';

			/* exec */
			$result = $db->query( $sql );
			if( $result === FALSE )
				throw new DBQueryError( $db->lasterror() );

			return $db->affected_rows($result);
		}
	}


	// see Year_Delete_Button
	public function deleteYearFromID($year, $delID) {
		$a = $this->getMatchArray($year);
		$num = sizeof($a);

		switch( $num ) {
			case 0:
				throw new AMEDeleteFailure('no result : wrong year ?');
				break;
			// sounds good
			case 1:
				$i = $a[0];
				// sounds very good, ok to delete
				if( $this->getTaxID($i) == $delID ) {
					// can throw DBQueryError
					$this->deleteTaxes( array($delID) );
					unset($this->taxes[$i]);
					$this->taxes = array_values($this->taxes);
					throw new AMEDeleteSuccess( '' );
				}
				throw new AMEDeleteFailure('Wrong ID : '.$delID);
				break;
			default:
				throw new AMEDeleteFailure('Multiple results with year='.$year);
				break;
		}
	}

	public function deleteYear($year) {
		global $db;

		$sql = "delete from $this->dbt where year=$year and level=0";

		/* exec */
		$result = $db->query( $sql );
		// don't throw any exception on failure
		// see taxEdit::__construct()
		//if( $result === FALSE )
		//	throw new DBQueryError( $db->lasterror() );

		return $db->affected_rows($result);
	}

	public function fetchTax($id) {
		global $db, $langs;

		// XXX : rounded values at taxes level ?
		//$sql = "select name, comment, level, year, quarter, type, first, second, round ";
		$sql = "select name, comment, level, year, quarter, type, accuracy, first, second ";
		$sql .= "from $this->dbt where rowid=".$id;

		/* exec */
		$result = $db->query( $sql );
		if( $result === FALSE )
			throw new DBQueryError( $db->lasterror() );

		$num = $db->num_rows( $result );
		if( $num != 1 )
			throw new DBQueryNoResult( $langs->trans('AME_NoResult', $id) );

		$this->taxes[] = $db->fetch_object( $result );
	}

	public function fetchTaxes() {
		global $db;

		// XXX : rounded values at taxes level ?
		//$sql = "select rowid, name, comment, level, year, quarter, type, first, second, round ";
		$sql = "select rowid, name, comment, level, year, quarter, type, accuracy, first, second ";
		$sql .= "from $this->dbt order by year, level, quarter, type ASC";

		/* exec */
		$result = $db->query( $sql );
		if( $result === FALSE )
			throw new DBQueryError( $db->lasterror() );

		$num = $db->num_rows( $result );

		$i = 0;
		while( $i < $num )
		{
			$this->taxes[] = $db->fetch_object( $result );
			$i++;
		}
		unset($obj);
	}

	// XXX : rounded values at taxes level ?
	//public function updateTax($id, $name, $comment, $type, $first, $second, $round, $quarter) {
	public function updateTax($id, $name, $comment, $type, $accuracy, $first, $second, $quarter) {
		global $db;

		$name = $db->escape($name);
		$comment = $db->escape($comment);

		$a = intval($accuracy);
		$f = intval($first);
		$s = intval($second);

		$sql  = "update $this->dbt set ";
		$sql .= "name='$name', comment='$comment', quarter='$quarter', ";
		// XXX : rounded values at taxes level ?
		//$sql .= "type='$type', first='$f', second='$s', round='$round' ";
		$sql .= "type='$type', accuracy='$a', first='$f', second='$s' ";
		$sql .= "where rowid='$id'";

		/* exec */
		$result = $db->query( $sql );
		if( $result === FALSE )
			throw new DBQueryError( $db->lasterror() );
	}

	/*
	 *	Private methods
	 *
	 */

	private function getTaxStringFromIndex($i) {
		$a = &$this->taxes[$i]->accuracy;
		$s = $this->taxes[$i]->second;

		// two digits
		if( $a == 2 )
			if( $s < 10 ) $s = '0'.$s;

		// three digits
		if( $a == 3 ) {
			if( $s >= 10 && $s < 100 )
				$s = '0'.$s;
			if( $s < 10 )
				$s = '00'.$s;
		}

		$s = $this->taxes[$i]->first.'.'.$s;
		return $s;
	}

}

?>
