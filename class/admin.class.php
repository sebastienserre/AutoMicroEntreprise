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
 *	\file		automicroent/class/admin.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Implements admin configuration page
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

require_once __DIR__.'/exceptions.class.php';
require_once __DIR__.'/taxes.class.php';
require_once __DIR__.'/categories.class.php';
require_once __DIR__.'/page.class.php';

class adminConfig extends AMEPage {

	private $taxes;
	private $bool = false;
	private $components;
	private $cats;

	private $level = NULL;
	private $year = NULL;
	private $new_year = NULL;
	private $years_array = NULL;

	function __construct() {
		global $langs, $db;

		$this->taxes = new taxesManager();
		$o = &$this->taxes;

		$form = GETPOST('form_name', '', 2);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// forms submit handling

		if( $form == 'year_select_form' ) {
			$this->year = GETPOST('year', '', 2);
			$this->level = GETPOST('level', 'int', 1);

			$urlopts = '?';
			if($this->year !== '1' && $this->year !== '0')
				$urlopts .= 'level='.$this->level.'&';
			if($this->year !== '0')
				$urlopts .= 'year='.$this->year;
			if($urlopts === '?')
				$urlopts = '';

			throw new AMEYearRedirection( $urlopts );
		}
		if( $form == 'new_year_form' ) {
			$this->new_year = GETPOST('new_year', 'int', 2);
			$o->insertTax($this->new_year, 0, 0);
		}
		else if ( $form == 'taxes_list' ) {
			$sup_id = GETPOST('sup_id', 'array', 2);
			$nb = $o->deleteTaxes($sup_id);
			$st = ($nb == 1) ? 'AME_TaxWasDeleted' : 'AME_TaxesWereDeleted';
			setEventMessage( $langs->trans($st, $nb) );
			unset( $nb, $st, $sup_id );
		}

		// fetch taxes table
		$o->fetchTaxes();

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// get and check year and level values from user

		$this->year = GETPOST('year', '');
		$y = &$this->year;

		// tax delete form was submitted ...
		if ( $form == 'taxes_list' ) {
			$match_array = $o->getMatchArray($y);
			// ... and there's no more tax recorded with this year
			if( sizeof($match_array) == 0 )
				setEventMessage( $langs->trans('AME_YearWasDeleted', $y) );

			unset($match_array);
		}

		$y_a = &$this->years_array;
		$y_a = $o->getYearsArray();

		// not NULL when new year form was submitted
		if( ! is_null($this->new_year) )
			$y = $this->new_year;
		// sanity check
		if( ! in_array( $y, $y_a, true ) )
			if( $y !== '1' )
				$y = '0';

		$this->cats = new categories($y);

		// only get
		$this->level = GETPOST('level', 'int', 1);
		$this->level = $this->cats->checkLevel($this->level);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// check for year delete
		// see Year_Delete_Button
		if( $y !== '1' and $y !== '0' )  { /*  year value is correct */
			// only get
			$delID = GETPOST('deleteID', 'int', 1);
			if( $delID !== '' ) {
				try {
					$o->deleteYearFromID($y, $delID);
				}
				catch( AMEDeleteFailure $e ) {
					setEventMessage( $langs->trans('AME_YearWasNotDeleted', $y, $e->getMessage()), 'warnings' );
					$y = '0';
				}
				catch( AMEDeleteSuccess $e ) {
					$y_a = array_diff( $y_a, array($y) );
					setEventMessage( $langs->trans('AME_YearWasDeleted', $y) );
					$y = '0';
				}
			}
		}

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

		$this->components = new Form($db);
	}

	function __destruct() {
	}

	public function showPage() {
		global $langs;

		$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
		print_fiche_titre($langs->trans('AME_Setup'),$linkback,'setup');

		$this->printSelectYearForm();

		if( $this->year === '0' ) { /* nothing selected or wrong value */
			$this->printPageFooter();
			return;
		}
		if( $this->year === '1' ) /* ask to record new year */
			$this->printNewYearForm();
		else {
			// only get
			if( GETPOST('insert', '', 1)  ===  'success' )
				setEventMessage( $langs->trans('AME_TaxInsertSuccess') );
			if( GETPOST('update', '', 1)  ===  'success' )
				setEventMessage( $langs->trans('AME_TaxUpdateSuccess') );
			$this->printTaxesTable();
		}

		$this->printPageFooter();

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function selectOption($value, $desc=NULL, $disabled=false) {
		$s = ($this->year === $value) ? ' selected="selected"' : '';
		$d = $disabled ? ' disabled="disabled"' : '';
		return PHP_EOL.'			<option'.$d.' style="text-align:right;" value="'.$value.'"'.$s.'>'.$desc.'</option>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function printSelectYearForm() {
		global $langs;

		$form_name = 'year_select_form';

		print '
<hr />
<div>
	<p>'.img_info().' '.$langs->trans('AME_YearsSelectDesc').'</p>
	<form action="'.$_SERVER["PHP_SELF"].'?level='.$this->level.'" name="'.$form_name.'" method="post">
	<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
	<input type="hidden" name="form_name" value="'.$form_name.'">
	<p style="padding-left:50px;">'.$langs->trans('AME_SelectedYear').'
	<select name="year" onchange="this.form.submit()">';

		$opts = $this->selectOption('0');
		$opts .= $this->selectOption('1', $langs->trans('AME_RecordNewYear'));
		foreach( $this->years_array as $y )
			$opts .= $this->selectOption($y, $y);

		print $opts.'
	</select>
	</p>
	</form>
</div><hr />';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function printNewYearForm() {
		global $langs;

		$current_year = strftime("%Y",time());
		$form_name = 'new_year_form';

		print '
<div>
	<form action="'.$_SERVER["PHP_SELF"].'" name="'.$form_name.'" method="post">
	<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
	<input type="hidden" name="form_name" value="'.$form_name.'">
	<p style="padding-left:50px;">'.$langs->trans('AME_PleaseSelectNewYear').'
	<select name="new_year" onchange="this.form.submit()">';
		$opts = $this->selectOption('1');
		for( $x = 2000; $x < $current_year+3; $x++) {
			if( ! in_array( $x, $this->years_array ) )
				$opts .= $this->selectOption($x, $x);
			else
				$opts .= $this->selectOption($x, $x, true);
		}
		print $opts.'
	</select>
	</p>
	</form>
</div>
';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function printTaxesTable() {
		global $bc, $langs;

		$o = &$this->taxes;

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// set tabs header
		$urlbase = $_SERVER['PHP_SELF'];
		$urlopts = '&amp;year='.$this->year;

		$h = 0;
		$head = array();

		$prefix = $this->cats->getTabPrefix();
		$info = NULL;

		foreach($this->cats->get() as $c) {
			if($c->getLevel() == $this->level )
				$info = $c->getInfo();
			$head[$h][0] = $urlbase.'?level='.$c->getLevel().$urlopts;
			$head[$h][1] = $c->getName();
			$head[$h][2] = $c->getTabName();
			$h++;
		}
		unset($c);

		$title = $this->year;

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// print tabs containing taxes table form

		dol_fiche_head($head, $prefix.$this->level, $title, 0);
		unset($h, $urlbase, $urlopts, $head, $title, $prefix);

		$match_array = $o->getMatchArray($this->year, $this->level);
		$can_submit = sizeof($match_array);

		$urlopts = '?year='.$this->year.'&amp;level='.$this->level;

		if( $can_submit > 0 ) {

			$v = $this->bool;
			$urledit = $_SERVER["PHP_SELF"];
			$urledit = dirname($urledit).'/edit.php';

			$form_name = 'taxes_list';

			print '
<form action="'.$_SERVER["PHP_SELF"].$urlopts.'" name="'.$form_name.'" method="post">

<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
<input type="hidden" name="form_name" value="'.$form_name.'">

<div><p>'.$info.'</p></div>

<table class="liste" width="100%">
	<tr class="liste_titre">
		<th class="liste_titre" style="padding-left:1em; text-align:left; width:22em;">
			'.$langs->trans('AME_TableColonName').'
		</th>
		<th class="liste_titre" style="text-align:left; width:40%;">
			'.$langs->trans('AME_TableColonComment').'
		</th>
		<th class="liste_titre" style="text-align:center;">
			'.$langs->trans('AME_TableColonYear').'
		</th>
		<th class="liste_titre" style="text-align:center; width:120px;">
			'.$langs->trans('AME_TableColonType').'
		</th>
		<th class="liste_titre" style="padding-right:1em; text-align:right;">
			'.$langs->trans('AME_TableColonValue').'
		</th>
		<th class="liste_titre" style="text-align:center;">
			'.$langs->trans('AME_TableColonModification').'
		</th>
		<th class="liste_titre" style="text-align:center;">
			'.$langs->trans('AME_TableColonDelete').'
		</th>
	</tr>';

			foreach($match_array as $i) {
				$y = $o->getTaxYear($i);
				$v = !$v;

				$n = $o->getTaxName($i);
				if( $n === 'AME_UPDATE' ) $n = '';
				print '
	<tr '.$bc[$v].'>
		<td style="padding-left:1em; text-align:left;">
			'.$n.'
		</td>
		<td style="text-align:left;">
			'.$o->getTaxComment($i).'
		</td>
		<td style="text-align:center;">
			'.$y.'
		</td>
		<td style="text-align:center;">
			'.$o->getTaxStringType($i).'
		</td>
		<td style="padding-right:1em; text-align:right;">
			'.$o->getTaxRate($i).' %
		</td>
		<td style="text-align:center;">
			<a href="'.$urledit.'?id='.$o->getTaxID($i).'" title="'.$langs->trans('AME_ModifyThisTax').'">'.img_edit().'</a>
		</td>
		<td style="text-align:center;">
			<input type="checkbox" name="sup_id[]" value="'.$o->getTaxID($i).'" onclick="return buttonState(this.name, \'sup_button\');" />
		</td>
	</tr>';
			}
			unset($urledit, $y, $n);

			print '
</table>
';
		}
		else {
			print '<p>'.$langs->trans('AME_NoRecordedTaxForThisYear', $this->year).'</p>';
		}

		dol_fiche_end();

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// print div containing action and submit buttons

		$urlnew = $_SERVER["PHP_SELF"];
		$urlnew = dirname($urlnew).'/edit.php'.$urlopts;

		print '
<div class="tabsAction">
	<div class="inline-block divButAction">
		<a class="butAction" href="'.$urlnew.'">' . $langs->trans('AME_NewTax') . '</a>
	</div>
	<div class="inline-block divButAction">';

		// see Year_Delete_Button
		$match_array = $o->getMatchArray($this->year, '0');
		$del = $langs->trans('AME_DeleteYear', $this->year);

		if( sizeof($match_array) == 1 ) {
			$urldel = $_SERVER["PHP_SELF"].'?year='.$this->year.'&amp;deleteID='.$o->getTaxID($match_array[0]);
			print '
		<a class="butAction" href="'.$urldel.'">'.$del.'</a>';
		}
		else {
			print '
		<span class="butActionRefused" title="'.$langs->trans('AME_DeleteYearDisabled', $this->year).'">'.$del.'</span>';
		}

		if( $can_submit > 0 )
			print '<input type="submit" id="sup_button" class="button" value="'.$langs->trans('AME_DeleteSelectedTaxes').'" />';
		print '
	</div>
</div>

</form>';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

}

?>
