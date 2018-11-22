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
 *	\file		automicroent/class/categories.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		File of categories class
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */

require_once __DIR__.'/enum.class.php';

class categories {
	// TODO
	// TODO	Allow user to create/manage/order its own categories
	// TODO Requirement : new db table
	// TODO

	private $container = array();

	function __construct($year) {
		define('TABPREFIX', 'tab_');

		if( $year < 2016 ) {
			$this->container[] = new category(1, 'AME_TaxesCat01');
			$this->container[] = new category(2, 'AME_TaxesCat02');
			$this->container[] = new category(3, 'AME_TaxesCat03', AME_ENUM::TAXROUND_ROUND);
			$this->container[] = new category(4, 'AME_TaxesCat04');
		}
		else {
			$this->container[] = new category(1, 'AME_TaxesCat01', AME_ENUM::TAXROUND_ROUND);
			$this->container[] = new category(2, 'AME_TaxesCat02', AME_ENUM::TAXROUND_ROUND);
			$this->container[] = new category(3, 'AME_TaxesCat03', AME_ENUM::TAXROUND_ROUND);
			$this->container[] = new category(4, 'AME_TaxesCat04', AME_ENUM::TAXROUND_ROUND);
		}
	}

	function __destruct() {
	}

	public function get() {
		return $this->container;
	}

	public function getTabPrefix() {
		return TABPREFIX;
	}

	public function checkLevel($level) {
		$ret = 1; // default category level
		foreach($this->container as $c) {
			if($c->getLevel() == $level) {
				$ret = $level;
				break;
			}
		}
		return (string)$ret;
	}
}

class category {
	private $level = -1;
	private $nameref = NULL;
	private $inforef = NULL;
	private $tabname = NULL;
	private $roundtype = NULL;

	function __construct($l, $n, $roundtype=AME_ENUM::TAXROUND_FLOOR, $inforef=NULL) {
		$this->level   = $l;
		$this->nameref = $n;
		$this->inforef = $inforef;
		$this->tabname = TABPREFIX.$l;
		$this->roundtype = $roundtype;
	}

	function __destruct() {
	}

	public function getLevel() {
		return (string)$this->level;
	}

	public function getName() {
		global $langs;
		return $langs->trans($this->nameref);
	}

	public function getTabName() {
		return $this->tabname;
	}

	// @param 	int $short	return short or long string (0 or not provided = long, 1 = short)
	public function getInfo($short=0) {
		global $langs;

		$roundref = NULL;
		switch( $this->roundtype ) {
			case AME_ENUM::TAXROUND_NONE:
				$roundref = 'AME_TaxRound_None';
				break;
			case AME_ENUM::TAXROUND_FLOOR:
				$roundref = 'AME_TaxRound_Floor';
				break;
			case AME_ENUM::TAXROUND_ROUND:
				$roundref = 'AME_TaxRound_Round';
				break;
			case AME_ENUM::TAXROUND_CEIL:
				$roundref = 'AME_TaxRound_Ceil';
				break;
			//default :
			//	throw new AMEUnknownENUM( $this->roundtype );
			//	break;
		}
		$roundref .= ($short == 1 ) ? '_Short' : '_Info';
		$info = $langs->trans( $roundref );
		if( $short != 1 && ! is_null($this->inforef) )
			$info .= '<br />'.$langs->trans($this->inforef);
		return $info;
	}

	public function getRoundedValue($value) {
		$ret = NULL;
		$r = $this->roundtype;
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
			//default :
			//	throw new AMEUnknownENUM( $r );
			//	break;
		}
		return $ret;
	}
}

?>
