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
 *	\file		automicroent/class/details.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Implements estimated loads details page
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */

require_once __DIR__.'/exceptions.class.php';
require_once __DIR__.'/page.class.php';
require_once __DIR__.'/taxes.class.php';
require_once __DIR__.'/categories.class.php';
require_once __DIR__.'/data.class.php';

class detailsTable extends AMEPage {

	private $bool = False;	// related to table lines colors

	private $taxes = NULL;

	private $year = 0;
	private $quarter = 0;
	private $month = NULL;

	private $cats = NULL;
	private $optioncss;

	function __construct() {
		// only from $_GET
		$this->year = GETPOST('year', 'int', 1);
		$this->quarter = (int)GETPOST('quarter', 'int', 1);
		$this->optioncss = GETPOST('optioncss','alpha');

		$this->taxes = new taxesManager();
		$o = &$this->taxes;

		$o->fetchTaxes();

		$years_array = $o->getYearsArray();

		// sanity check
		if( ! in_array( $this->year, $years_array, true ) )
			throw new AMEWrongYear( $this->year );

		$quarters = array(1, 2, 3, 4);
		if( ! in_array( $this->quarter, $quarters, true ) )
			throw new AMEWrongQuarter( $this->quarter );
		$quarters = array(1, 4, 7, 10);
		$this->month = $quarters[$this->quarter - 1];
		unset($quarters);

		$this->cats = new categories($this->year);

		$this->data = new reportData($this->year, 1, $o);
	}

	function __destruct() {
	}

	public function showPage() {
		global $bc, $langs;

		$o = &$this->taxes;

		$v = $this->bool;

		// css styles
		$colors = array();
		$colors[0] = '#B8DBFF'; // products
		$colors[1] = '#FFFFB8'; // services
		$colors[2] = '#FFB8B8'; // both

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// page title
		$urlback = dirname( $_SERVER['PHP_SELF'] ).'/index.php';

		$linkback='<a href="'.$urlback.'">'.$langs->trans('AME_BackToPreviousPage').'</a>';
		if( $this->optioncss != 'print' )
			print_fiche_titre($langs->trans('AME_EstimatedLoadsDetailsTitle', $this->quarter, $this->year), $linkback, 'setup');
		unset($linkback);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// taxs list

		$match_array = $o->getMatchArray($this->year);

		print_titre($langs->trans('AME_RecordedTaxsList', $this->year));

		print '
<table class="liste" style="margin:10px 0; width:80%;">
	<tr class="liste_titre">
		<th class="liste_titre" style="padding-left:1em; width:22em; text-align:left;">'.$langs->trans('AME_Category').'</th>
		<th class="liste_titre" style="padding-left:1em; width:22em; text-align:left;">'.$langs->trans('AME_TableColonName').'</th>
		<th class="liste_titre" style="width:120px; text-align:center;">'.$langs->trans('AME_TableColonType').'</th>
		<th class="liste_titre" style="width:50px; padding-right:1em; text-align:right;">'.$langs->trans('AME_TableColonValue').'</th>
	</tr>';

		foreach($match_array as $i) {
			$v = !$v;

			$n = $o->getTaxName($i);
			$l = $o->getTaxLevel($i);
			foreach($this->cats->get() as $c) {
				if($c->getLevel() == $l )
					$l = $c->getName();
			}
			unset($c);
			if( $n === 'AME_UPDATE' ) $n = '';
			print '
	<tr '.$bc[$v].'>
		<td style="text-align:left; padding-left:1em;">'.$l.'</td>
		<td style="text-align:left; padding-left:1em;">'.$n.'</td>
		<td style="text-align:center;">'.$o->getTaxStringType($i).'</td>
		<td style="text-align:right; padding-right:1em; background-color:'.$colors[$o->getTaxType($i)].';">'.$o->getTaxRate($i).' %</td>
	</tr>';
			}
			unset($l, $n);
			print '
</table>
';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// quarter amounts by type

		print_titre($langs->trans('AME_QuarterTurnoversByType', $this->quarter, $this->year));

		$v = $this->bool;

		$a = $this->data->getQuarterProductsAmount($this->year, $this->month);
		$b = $this->data->getQuarterServicesAmount($this->year, $this->month);
		$c = $this->data->getQuarterAmount($this->year, $this->month);

		$s1 = 'width:30%; text-align:center;';
		$s2 = 'width:40%; text-align:center;';

		print '
<table class="liste" style="margin:10px 0; width:30%;">
	<tr class="liste_titre">
		<th class="liste_titre" style="'.$s1.'">'.$langs->trans('AME_Products').'</th>
		<th class="liste_titre" style="'.$s1.'">'.$langs->trans('AME_Services').'</th>
		<th class="liste_titre" style="'.$s2.'">'.$langs->trans('AME_ProductsAndServices').'</th>
	</tr>
	<tr '.$bc[$v].'>
		<td style="'.$s1.' background-color:'.$colors[0].';">'.$a.'</td>
		<td style="'.$s1.' background-color:'.$colors[1].';">'.$b.'</td>
		<td style="'.$s2.' background-color:'.$colors[2].';">'.$c.'</td>
	</tr>
</table>';
		unset($s1, $s2);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// go back button

		if( $this->optioncss != 'print' )
			print '
<div align="center" style="margin-top:32px;">
	<a class="butAction" href="'.$urlback.'">'.$langs->trans('AME_BackToPreviousPage').'</a>
</div><br /><br />';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// estimated loads calculation details

		print_titre($langs->trans('AME_EstimatedLoadsCalculationDetails'));

		print '
<div style="width:70%;">
		<p>'.img_info().' '.$langs->trans('AME_2015RoundExplanation').' '.$langs->trans('AME_2016RoundExplanation').'</p>
</div>';

		$v = $this->bool;

		$s1 = 'text-align:center;';
		print '
<table class="liste" style="margin:10px 0; width:80%;">
	<tr class="liste_titre">
		<th class="liste_titre" style="'.$s1.' width:200px;">'.$langs->trans('AME_UsedTurnover').'</th>
		<th class="liste_titre" style="'.$s1.' width:1em;">&nbsp;</th>
		<th class="liste_titre" style="'.$s1.' width:10%;">'.$langs->trans('AME_UsedRate').'</th>
		<th class="liste_titre" style="'.$s1.' width:1em;">&nbsp;</th>
		<th class="liste_titre" style="'.$s1.' width:10%;">'.$langs->trans('AME_OperationResult').'</th>
		<th class="liste_titre" style="'.$s1.' width:140px;">'.$langs->trans('AME_RoundType').'</th>
		<th class="liste_titre" style="'.$s1.' width:10%;">'.$langs->trans('AME_TableColonRound').'</th>
		<th class="liste_titre" style="'.$s1.'">&nbsp;</th>
	</tr>';

		$turnover = NULL;
		$current_level = 1;
		$max = sizeof($match_array);
		$j = 0;
		$cpt = 0;
		$sum = 0;
		$roundssum = 0;

		foreach($match_array as $i) {
			$j++; $cpt++;
			$v = !$v;

			$l = $o->getTaxLevel($i);
			$dosum = false;
			if( $current_level < $l ) {
				$current_level = $l;
				$dosum = true;
			}

			if( $dosum ) {
				$round = $sum;
				$rs = $this->getRoundShortString($l, $round);
				$this->printCategorySumTableLines($v, $s1, $sum, $rs, $round);
				$roundssum += $round;
				unset($rs);
				$v = !$v;
				$cpt = 1;
				$sum = 0;
			}

			if( $cpt > 1 ) {
				$this->printResultLine($v, $s1, '+');
				$v = !$v;
			}

			$t = $o->getTaxType($i);
			$color = $colors[$t];
			switch( $t ) {
				case 0:
					$turnover = &$a;
					break;
				case 1:
					$turnover = &$b;
					break;
				case 2:
					$turnover = &$c;
					break;
			}
			$r = $o->getTaxRate($i);
			$result = $turnover * $r / 100;
			$sum += $result;

			print '
	<tr '.$bc[$v].'>
		<td style="'.$s1.' background-color:'.$color.';">'.$turnover.'</td>
		<td style="'.$s1.'">*</td>
		<td style="'.$s1.'">'.$r.' %</td>
		<td style="'.$s1.'">=</td>
		<td style="'.$s1.'">'.$result.'</td>
		<td style="'.$s1.'"></td>
		<td style="'.$s1.'"></td>
		<td style="'.$s1.'"></td>
	</tr>';

			if($j == $max) { // last occurence in the foreach loop
				$v = !$v;
				$round = $sum;
				$rs = $this->getRoundShortString($l+1, $round);
				$this->printCategorySumTableLines($v, $s1, $sum, $rs, $round);
				$roundssum += $round;
				unset($rs);
			}
		} // end foreach

		$this->printResultLine($v, $s1, '&nbsp;');

		print '
	<tr '.$bc[$v].'>
		<td style="text-align:right;" colspan="6"><b>'.$langs->trans('AME_RoundsSum').'</b></td>
		<td style="'.$s1.'"><b>'.$roundssum.'</b></td>
		<td style="'.$s1.'"></td>
	</tr>';

		$this->printResultLine($v, $s1, '&nbsp;');

		print '
</table>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// go back button

		if( $this->optioncss != 'print' )
			print '
<div align="center" style="margin-top:32px;">
	<a class="butAction" href="'.$urlback.'">'.$langs->trans('AME_BackToPreviousPage').'</a>
</div><br /><br />';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// Estimated loads by months

		print_titre($langs->trans('AME_EstimatedLoadsDetailsByMonths', $this->quarter, $this->year));

		print '
<div>
		<p>'.img_info().' '.$langs->trans('AME_EstimatedLoadsMonthByMonthExplanation').'</p>
</div>';

		$v = $this->bool;
		$v = !$v;

		$s1 = 'width:20%; text-align:center;';
		$s2 = 'width:20%; text-align:center;';

		print '
<table class="liste" style="margin:10px 0; width:80%;">
	<tr class="liste_titre">
		<th class="liste_titre" style="'.$s1.'">&nbsp;</th>
		<th class="liste_titre" style="'.$s2.'">'.dol_print_date(dol_mktime(12,0,0,$this->month,1,$this->year),'%B').'</th>
		<th class="liste_titre" style="'.$s2.'">'.dol_print_date(dol_mktime(12,0,0,$this->month+1,1,$this->year),'%B').'</th>
		<th class="liste_titre" style="'.$s2.'">'.dol_print_date(dol_mktime(12,0,0,$this->month+2,1,$this->year),'%B').'</th>
		<th class="liste_titre" style="'.$s2.'"><b>'.$langs->trans('AME_Sums').'</b></th>
	</tr>
	<tr '.$bc[$v].'>
		<td style="'.$s1.' background-color:'.$colors[0].'"><b>'.$langs->trans('AME_Products').'</b></td>
		<td style="'.$s2.'">'.$this->data->getMonthProductsAmount($this->year, $this->month).'</td>
		<td style="'.$s2.'">'.$this->data->getMonthProductsAmount($this->year, $this->month+1).'</td>
		<td style="'.$s2.'">'.$this->data->getMonthProductsAmount($this->year, $this->month+2).'</td>
		<td style="'.$s2.' background-color:'.$colors[0].'">'.$a.'</td>
	</tr>';

		$v = !$v;
		print '
	<tr '.$bc[$v].'>
		<td style="'.$s1.' background-color:'.$colors[1].'"><b>'.$langs->trans('AME_Services').'</b></td>
		<td style="'.$s2.'">'.$this->data->getMonthServicesAmount($this->year, $this->month).'</td>
		<td style="'.$s2.'">'.$this->data->getMonthServicesAmount($this->year, $this->month+1).'</td>
		<td style="'.$s2.'">'.$this->data->getMonthServicesAmount($this->year, $this->month+2).'</td>
		<td style="'.$s2.' background-color:'.$colors[1].'">'.$b.'</td>
	</tr>';

		$v = !$v;
		print '
	<tr '.$bc[$v].'>
		<td style="'.$s1.' background-color:'.$colors[2].'"><b>'.$langs->trans('AME_ProductsAndServices').'</b></td>
		<td style="'.$s2.'">'.$this->data->getMonthAmount($this->year, $this->month).'</td>
		<td style="'.$s2.'">'.$this->data->getMonthAmount($this->year, $this->month+1).'</td>
		<td style="'.$s2.'">'.$this->data->getMonthAmount($this->year, $this->month+2).'</td>
		<td style="'.$s2.' background-color:'.$colors[2].'">'.$c.'</td>
	</tr>';

		$v = !$v;
		print '
	<tr '.$bc[$v].'>
		<td style="'.$s1.' border-top:1px dotted #000;">&nbsp;</td>
		<td style="'.$s2.' border-top:1px dotted #000;"></td>
		<td style="'.$s2.' border-top:1px dotted #000;"></td>
		<td style="'.$s2.' border-top:1px dotted #000;"></td>
		<td style="'.$s2.' border-top:1px dotted #000;"></td>
	</tr>';



		$done = array();
		$months = array();

		$i = 0;
		while( $i < 3 ) {
			$months[] = $this->data->getMonthLoads($this->year, $this->month+$i);
			$i++;
		}

/*
		print '<pre>';
		print_r( $months );
		print '</pre>';
*/

		foreach($match_array as $tax) {

			$category = '';
			$level = $o->getTaxLevel($tax);
			if( in_array( $level, $done ) )
				continue;
			foreach($this->cats->get() as $c) {
				if($c->getLevel() == $level) {
					$done[] = $level;
					$category = $c->getName();
				}
			}

			$v = !$v;
			print '
	<tr '.$bc[$v].'>
		<td style="'.$s1.'">'.$category.'</td>
		<td style="'.$s2.'">'.$months[0][$level].'</td>
		<td style="'.$s2.'">'.$months[1][$level].'</td>
		<td style="'.$s2.'">'.$months[2][$level].'</td>
		<td style="'.$s2.'"></td>
	</tr>';

		}
		unset($c, $level, $tax);


		$t1 = array_sum($months[0]);
		$t2 = array_sum($months[1]);
		$t3 = array_sum($months[2]);

		$v = !$v;
		print '
	<tr '.$bc[$v].'>
		<td style="'.$s1.' border-top:1px dotted #000;">&nbsp;</td>
		<td style="'.$s2.' border-top:1px dotted #000;"></td>
		<td style="'.$s2.' border-top:1px dotted #000;"></td>
		<td style="'.$s2.' border-top:1px dotted #000;"></td>
		<td style="'.$s2.' border-top:1px dotted #000;"></td>
	</tr>
	<tr '.$bc[$v].'>
		<td style="'.$s1.'"><b>'.$langs->trans('AME_Sums').'</b></td>
		<td style="'.$s2.'">'.$t1.'</td>
		<td style="'.$s2.'">'.$t2.'</td>
		<td style="'.$s2.'">'.$t3.'</td>
		<td style="'.$s2.'"><b>'.($t1+$t2+$t3).'</b></td>
	</tr>
</table>';

		unset($s1, $s2);


		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// go back button + footer


		if( $this->optioncss != 'print' )
			print '
<div align="center" style="margin-top:32px;">
	<a class="butAction" href="'.$urlback.'">'.$langs->trans('AME_BackToPreviousPage').'</a>
</div>';

		$this->printPageFooter();

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function printCategorySumTableLines(&$v, $s1, $sum, $rs, $round) {
		$this->printResultLine($v, $s1, '=');
		$v = !$v;
		$this->printResultLine($v, $s1, $sum, $rs, $round);
		$v = !$v;
		$this->printResultLine($v, $s1, '&nbsp;');

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function printResultLine($v, $s1, $st, $rs=NULL, $round=NULL) {
		global $bc;

		print '
	<tr '.$bc[$v].'>
		<td style="'.$s1.'"></td>
		<td style="'.$s1.'"></td>
		<td style="'.$s1.'"></td>
		<td style="'.$s1.'"></td>
		<td style="'.$s1.'">'.$st.'</td>
		<td style="'.$s1.'">'.$rs.'</td>
		<td style="'.$s1.'">'.$round.'</td>
		<td style="'.$s1.'"></td>
	</tr>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function getRoundShortString($l, &$round) {
		$roundstring = '?!?';
		foreach($this->cats->get() as $c) {
			if( $c->getLevel() == ($l - 1) ) {
				$roundstring = $c->getInfo(1);
				$round = $c->getRoundedValue($round);
			}
		}
		return $roundstring;
	}

}

?>
