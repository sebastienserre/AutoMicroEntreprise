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
 *	\file		automicroent/class/report.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Implements report table page
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';

require_once __DIR__.'/data.class.php';
require_once __DIR__.'/page.class.php';
require_once __DIR__.'/taxes.class.php';

class reportTable extends AMEPage {

	private $data = NULL;
	private $taxes = NULL;
	private $msg = NULL;

	private $years_number = 3;	// number of years displayed
	private $year_start = NULL;	// first year displayed
	private $year_end = NULL;	// last year displayed

	private $bool = False;		// related to table lines colors

	function __construct() {
		// $idmenu = GETPOST('idmenu');
		$year = GETPOST('year_start');

		$year_current = strftime("%Y",time());
		if (! $year) {
			$this->year_start = $year_current - ($this->years_number-1);
			$this->year_end = $year_current;
		}
		else {
			$this->year_start = $year;
			$this->year_end = $this->year_start + ($this->years_number-1);
		}

		try {
			$this->taxes = new taxesManager();
			$this->taxes->fetchTaxes();
			$this->data = new reportData($this->year_start, $this->years_number, $this->taxes);
		}
		catch( DBQueryNoResult $e ) {
			$this->msg = $e->getMessage();
			return;
		}

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	function __destruct() {
	}

	public function showPage() {
		$this->printReportHeader();
		$this->printReportTable();
		$this->printPageFooter();

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	public function showNoResultPage() {
		$this->printReportHeader();
		print '<p>'.$this->msg.'</p>';
		$this->printPageFooter();

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	public function checkForResults() {
		return is_null($this->msg);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function printReportHeader() {
		global $langs;

		$name = $langs->trans('AME_ReportName');
		$desc = $langs->trans('AME_ReportDesc');
		$period = "$this->year_start - $this->year_end";
		// idmenu='.$idmenu.'&
		$urllink = $_SERVER['PHP_SELF'].'?year_start=';
		$periodlink  = '<a href="'.$urllink.($this->year_start-1).'">'.img_previous().'</a>&nbsp;';
		$periodlink .= '<a href="'.$urllink.($this->year_start+1).'">'.img_next().'</a>';
		$builddate = time();

		$nomlink = '';
		$exportlink = '';
		// 'idmenu' => $idmenu
		$moreparam = array('year_start' => $this->year_start);
		report_header(	$name, $nomlink, $period, $periodlink,
				$desc, $builddate, $exportlink, $moreparam );

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function printReportTable() {
		global $langs, $bc;

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// table header

		print '
<table class="noborder" width="100%">

	<tr class="liste_titre">
		<td style="width:15%;">
			&nbsp;
		</td>';

		for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
			print '
		<td align="center" colspan="2" style="padding-left:10%">
			<b>'.$y.'</b>
		</td>';
		}
		print '
	</tr>

	<tr class="liste_titre">
		<td class="liste_titre">
		</td>';
		for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
			print '
		<td align="right">
			<b>'.$langs->trans('AME_Products').'</b>
		</td>
		<td align="right">
			<b>'.$langs->trans('AME_Services').'</b>
		</td>';
		}
		print '
	</tr>';

		$v = $this->bool;

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// Loop on each month

		for ($m = 1 ; $m <= 12 ; $m++) {
			$v = !$v;

			/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
			// months

			print '
	<tr '.$bc[$v].'>
		<td align="right">
			<b>'.dol_print_date(dol_mktime(12,0,0,$m,1,$this->year_start),'%B').'</b>
		</td>';
			for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
				$ref = $this->data->getVPRefStringFromDate($y, $m);
				$a = $this->data->getMonthProductsAmount($y, $m);
				$b = $this->data->getMonthServicesAmount($y, $m);
			print 	'
		<td align="right">'.$a.' '.$ref.'</td>
		<td align="right">'.$b.' '.$ref.'</td>';
			}
			unset($a, $b, $ref);

			print '
	</tr>';

			/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
			// quarter

			if( $m%3 == 0 ) {
				$quarter = ($m / 3);	// quarter number
				$quarterStart = $m - 2; // month number when new quarter start

				$v = !$v;
				print '
	<tr '.$bc[$v].'>
		<td style="border-top:1px dotted #000; padding-right:20px; text-align:center;"><b>'.$langs->trans('AME_Quarter').' '.$quarter.'</b></td>';
				for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
					$a = $this->data->getQuarterProductsAmount($y, $quarterStart);
					$b = $this->data->getQuarterServicesAmount($y, $quarterStart);
					print '
		<td align="right" style="border-top:1px dotted #000;">'.$a.'</td>
		<td align="right" style="border-top:1px dotted #000;">'.$b.'</td>';
				}
				print '
	</tr>';

				/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
				// quarter (products + services) accumulation

				//$v = !$v;
				print '
	<tr '.$bc[$v].'>
		<td style="padding-right:20px; text-align:center;">'.$langs->trans('AME_Accumulation').'</td>';
				for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
					$a = $this->data->getQuarterAmount($y, $quarterStart);
					print '
		<td colspan="2" style="text-align:right; padding-right:75px;">'.$a.'</td>';
				}
				print '
	</tr>';

				/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
				// quarter estimated loads

				//$v = !$v;
				print '
	<tr '.$bc[$v].'>
		<td style="text-align:center; background-color:#ddd;"><b>'.$langs->trans('AME_EstimatedLoads').'</b></td>';
				$url = dirname( $_SERVER['PHP_SELF'] ).'/details.php';
				for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
					$a = $this->data->getQuarterLoads($y, $quarterStart);
					$title = $langs->trans('AME_EstimatedLoadsDetails', $quarter, $y);
					$a = '<a href="'.$url.'?year='.$y.'&amp;quarter='.$quarter.'" title="'.$title.'">'.$a.'</a>';
					print '
		<td colspan="2" style="text-align:right; padding-right:80px; background-color:#ddd;">'.$a.'</td>';
				}
				unset( $a, $url, $title );
				print '
	</tr>';

				/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
				// empty line

				//$v = !$v;
				$style = ($m < 12) ? 'style="border-bottom:1px dotted #000;"' : '';
				print '
	<tr '.$bc[$v].'>
		<td '.$style.'></td>';
				for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
					print '
		<td align="right" '.$style.'>&nbsp;</td>
		<td align="right" '.$style.'>&nbsp;</td>';
				}
				print '
	</tr>';

				unset($quarter, $quarterStart);
			} // end if( $m%3 == 0 )
		} // end for

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

		print '
	<tr class="liste_titre">
		<td style="width:15%;">
			&nbsp;
		</td>';

		for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
			print '
		<td align="center" colspan="2" style="padding-left:10%">
			<b>'.$y.'</b>
		</td>';
		}
		print '
	</tr>

	<tr class="liste_titre">
		<td class="liste_titre">
		</td>';
		for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
			print '
		<td align="right">
			<b>'.$langs->trans('AME_Products').'</b>
		</td>
		<td align="right">
			<b>'.$langs->trans('AME_Services').'</b>
		</td>';
		}
		print '
	</tr>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// year sum

		//$v = !$v;
		print '
	<tr '.$bc[$v].'>
		<td style="padding-right:20px; text-align:center;"><b>'.$langs->trans('AME_YearSum').'</b></td>';
		for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
			$a = $this->data->getYearProductsAmount($y);
			$b = $this->data->getYearServicesAmount($y);
			print '
		<td style="text-align:right;">'.$a.'</td>
		<td style="text-align:right;">'.$b.'</td>';
		}
			print '
	</tr>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// year accumulation

		//$v = !$v;
		print '
	<tr '.$bc[$v].'>
		<td style="padding-right:20px; text-align:center;">'.$langs->trans('AME_Accumulation').'</td>';
		for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
			$a = $this->data->getYearAmount($y);
			print '
		<td colspan="2" style="text-align:right; padding-right:75px;">'.$a.'</td>';
		}
		print '
	</tr>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// year estimated loads

		//$v = !$v;
		print '
	<tr '.$bc[$v].'>
		<td style="text-align:center; background-color:#ddd;"><b>'.$langs->trans('AME_EstimatedLoads').'</b></td>';
		for ($y = $this->year_start ; $y <= $this->year_end ; $y++) {
			$a = $this->data->getYearLoads($y);
			print '
		<td colspan="2" style="text-align:right; padding-right:80px; background-color:#ddd;">'.$a.'</td>';
		}
		print '
	</tr>';

		print '
</table>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// partial payments distribution

		if( $this->data->getVPNumber() == 0 )
			return;

		$v = $this->bool;

		print '<br />';
		print_fiche_titre( $langs->trans('AME_Distribution') );

		print '<div><p>'.$langs->trans('AME_PartialPaymentEx').'</p></div>';
		print '
<table class="noborder" width="100%">
	<tr class="liste_titre">
		<td style="width:100px;" align="center"><b>'.$langs->trans('AME_Reference').'</b></td>
		<td align="center"><b>'.$langs->trans('AME_Invoice').'</b></td>
		<td align="center"><b>'.$langs->trans('AME_Month').' / '.$langs->trans('AME_Year').'</b></td>
		<td align="center"><b>'.$langs->trans('AME_InvoiceAmount').'</b></td>
		<td align="center"><b>'.$langs->trans('AME_PaymentAmount').'</b></td>
		<td align="center"><b>'.$langs->trans('AME_Products').'</b></td>
		<td align="center"><b>'.$langs->trans('AME_Services').'</b></td>
	</tr>';

		$comp = versioncompare(versiondolibarrarray(), array(6,0,0));

		for( $i=1; $i <= $this->data->getVPLastRef(); $i++ ) {
			$v = !$v;

			$id = $this->data->getVPInvoiceID($i);
			$num = $this->data->getVPInvoiceNumber($i);
			if( $comp < 0 ) /* dolibarr version lower than 6.0.0 */
				$url = DOL_URL_ROOT.'/compta/facture.php?facid='.$id;
			else
				$url = DOL_URL_ROOT.'/compta/facture/card.php?facid='.$id;
			$link = '<a href="'.$url.'" title="'.$langs->trans('AME_Invoice').' '.$num.'">'.$num.'</a>';

			print '
	<tr '.$bc[$v].'>
		<td style="width:100px;" align="center">('.$i.')</td>
		<td align="center">'.$link.'</td>
		<td align="center">'.$this->data->getVPInvoiceDateString($i).'</td>
		<td align="center">'.$this->data->getVPInvoiceAmount($i).'</td>
		<td align="center">'.$this->data->getVPPaymentAmount($i).'</td>
		<td align="center">'.$this->data->getVPProductsString($i).'</td>
		<td align="center">'.$this->data->getVPServicesString($i).'</td>
	</tr>';
		}
		unset($url, $link, $id, $num);

		print '
</table>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

}

?>
