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
 *	\file		automicroent/class/edit.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Implements taxes modification
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

require_once __DIR__.'/exceptions.class.php';
require_once __DIR__.'/enum.class.php';
require_once __DIR__.'/taxes.class.php';
require_once __DIR__.'/page.class.php';
require_once __DIR__.'/categories.class.php';

class taxEdit extends AMEPage {

	private $taxes = NULL;
	private $rateID = 0;
	private $components = NULL;
	private $form_name = 'register_rate';
	private $mesg = NULL;

	private $new_t = NULL;
	private $new_n = NULL;
	private $new_c = NULL;
	private $new_a = NULL;
	private $new_f = NULL;
	private $new_s = NULL;
	// XXX : rounded values at taxes level ?
	//private $new_r = NULL;

	// used only on new tax form
	private $new_y = NULL;
	private $new_l = NULL;

	// default values
	private $def_t = -1;	// type
	private $def_n = '';	// name
	private $def_c = '';	// comment
	private $def_a = 2;	// default percentage accuracy (2 or 3 decimals)
	private $def_f = 0;	// first
	private $def_s = 0;	// second
	// XXX : rounded values at taxes level ?
	//private $def_r = -1;	// round
	private $def_y = NULL;	// year
	private $def_l = 1;	// level

	function __construct() {
		global $db, $langs;

		$this->taxes = new taxesManager();
		$o = &$this->taxes;

		$form = GETPOST('form_name', '', 2);

		$this->rateID = GETPOST('id', 'int', 3);
		if( $this->rateID == '' )
			$this->rateID = '0';

		// if ID is not defined, trying to create a new tax
		if( $this->rateID === '0' ) {
			// getting values from $_POST, then from $_GET
			$this->new_y = GETPOST('year', 'int', 3);
			$this->new_l = GETPOST('level', 'int', 3);

			// don't need to go further in this case
			if( $this->new_y == '' )
				throw new AMEWrongYear( '' );
		}

		// form was submitted
		if( $form == $this->form_name ) {
			// get new values from $_POST
			$this->new_t = GETPOST('type', 'int', 2);
			$this->new_n = GETPOST('tax_name', '', 2);
			$this->new_c = GETPOST('tax_comment', '', 2);
			$this->new_a = GETPOST('percentage_accuracy', 'int', 2);
			$this->new_f = GETPOST('int_value', 'int', 2);
			$this->new_s = GETPOST('part_value', 'int', 2);
			// XXX : rounded values at taxes level ?
			//$this->new_r = GETPOST('roundtype', 'int', 2);

			//$quarter = GETPOST('quarter', '', 2);
			$quarter = '0';

			/*
			 * if values are empty_from_GETPOST
			 * 	if ID == 0, attempt to create a new tax, reset values to NULL.
			 * 	if ID != 0, attempt to edit tax with real ID, keep user's values.
			 */
			$reset = ( $this->rateID === '0' ) ? true : false;

			$this->checkNewValue($this->new_t, $reset, 'AME_TableColonType');
			$this->checkNewValue($this->new_n, $reset, 'AME_TableColonName');
			// No need to check it since it is radio button and default value is defined but,
			// if we ever check it, we must first fix a charset issue : ''Pr&eacute;cision'
			//$this->checkNewValue($this->new_a, $reset, 'AME_PercentageAccuracy');
			$this->checkNewValue($this->new_c, $reset);
			$this->checkNewValue($this->new_f, $reset);
			$this->checkNewValue($this->new_s, $reset);
			// XXX : rounded values at taxes level ?
			//$this->checkNewValue($this->new_r, $reset, 'AME_TableColonRound');

			unset($reset);

			if( ! is_null($this->mesg) )
				setEventMessage( $this->mesg, 'errors' );
			else { /* required fields are not empty, ok to update DB */
				try {
					if( $this->rateID !== '0' ) {
						$o->updateTax(
							$this->rateID, $this->new_n, $this->new_c,
							$this->new_t, $this->new_a, $this->new_f, $this->new_s,
							$quarter );
							// XXX : rounded values at taxes level ?
							//$this->new_r, $quarter );

						// update success, getting values from $_POST
						$this->new_y = GETPOST('year', 'int', 2);
						$this->new_l = GETPOST('level', 'int', 2);
						$urlopts = 'level='.$this->new_l.'&year='.$this->new_y;
						throw new DBQueryTaxUpdateSuccess( $urlopts );
					}
					else {
						$o->insertTax(
							$this->new_y, $this->new_l, $this->new_t,
							$this->new_n, $this->new_c,
							$this->new_a, $this->new_f, $this->new_s,
							$quarter);
							// XXX : rounded values at taxes level ?
							//$this->new_r, $quarter);

						// delete year with level=0 after successful insert
						$o->deleteYear($this->new_y);
						$urlopts = 'level='.$this->new_l.'&year='.$this->new_y;
						throw new DBQueryTaxInsertSuccess( $urlopts );
					}
				}
				catch( DBQueryError $e ) {
					$this->mesg = $langs->trans('AME_TaxUpdateFailure', $e->getMessage());
					setEventMessage( $this->mesg, 'errors' );
				}
			}
			unset($quarter);
		}

		try {
			// if ID == 0 (wrong value or new tax form), throws DBQueryNoResult ...
			$o->fetchTax($this->rateID);
			$this->def_t = $o->getTaxType();
			$this->def_n = $o->getTaxName();
			$this->def_c = $o->getTaxComment();
			$this->def_a = $o->getTaxAccuracy();
			$this->def_f = $o->getTaxFirst();
			$this->def_s = $o->getTaxSecond();
			// XXX : rounded values at taxes level ?
			//$this->def_r = $o->getTaxRound();

			$this->def_y = $o->getTaxYear();
			$this->def_l = $o->getTaxLevel();
		}
		catch( DBQueryNoResult $e ) {
			$o->fetchTaxes();

			// new_y will not be used, just to check the level
			$cats = new categories($this->new_y);
			$this->new_l = $cats->checkLevel($this->new_l);
			unset($cats);

			$years_array = $o->getYearsArray();

			// sanity check
			if( ! in_array( $this->new_y, $years_array, true ) )
				throw new AMEWrongYear( $this->new_y );

			// ... then others def_x values will be setted up to defaults
			$this->def_y = $this->new_y;
			$this->def_l = $this->new_l;
		}

		$this->components = new Form($db);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	function __destruct() {
	}

	public function showPage() {
		global $langs;

		$f_n = &$this->form_name;

		// values may be NULL when ID == 0 (attempt to create a new tax).
		// we are using default values then, else we are using user's values.
		$t = (is_null($this->new_t)) ? $this->def_t : $this->new_t;	// type
		$n = (is_null($this->new_n)) ? $this->def_n : $this->new_n;	// name
		$c = (is_null($this->new_c)) ? $this->def_c : $this->new_c;	// comment
		$f = (is_null($this->new_f)) ? $this->def_f : $this->new_f;	// first
		$s = (is_null($this->new_s)) ? $this->def_s : $this->new_s;	// second
		// XXX : rounded values at taxes level ?
		//$r = (is_null($this->new_r)) ? $this->def_r : $this->new_r;	// round
		$y = $this->def_y;	// year and level are defined
		$l = $this->def_l;	// into constructor

		$a = (is_null($this->new_a)) ? $this->def_a : $this->new_a;	// percentage accuracy

		if( is_null($c) ) $c = '';

		if( $n === 'AME_UPDATE' ) $n = '';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// print title and linkback

		$urlback = $_SERVER['PHP_SELF'];
		$urlback = dirname($urlback).'/index.php';
		$urlback .= '?level='.$l.'&amp;year='.$y;

		$linkback='<a href="'.$urlback.'">'.$langs->trans('AME_BackToPreviousPage').'</a>';
		$strref = ($this->rateID === '0') ? 'AME_InsertTitle' : 'AME_EditTitle';
		print_fiche_titre( $langs->trans($strref), $linkback );
		unset($linkback, $strref);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		// print form and table

		print '
<form action="'.$_SERVER["PHP_SELF"].'" name="'.$f_n.'" method="post">
	<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
	<input type="hidden" name="id" value="'.$this->rateID.'">
	<input type="hidden" name="year" value="'.$y.'">
	<input type="hidden" name="level" value="'.$l.'">
	<input type="hidden" name="form_name" value="'.$f_n.'">
	<input type="hidden" name="accuracy" value="'.$a.'">
	<input type="hidden" name="int_value" value="'.$f.'">
	<input type="hidden" name="part_value" value="'.$s.'">
	<input type="hidden" name="quarter" value="0">

<table class="border" width="100%">
	<tr>
		<td>
			'.$langs->trans('AME_Category').'
		</td>
		<td colspan="2">
			'.$langs->trans('AME_TaxesCat0'.$l).'
		</td>
	</tr>
	<tr>
		<td class="fieldrequired">
			'.$langs->trans('AME_TableColonName').'
		</td>
		<td colspan="2">
			<input type="text" maxlength="44" size="50" name="tax_name" value="'.$n.'" />
		</td>
	</tr>
	<tr>
		<td>
			'.$langs->trans('AME_TableColonComment').'
		</td>
		<td colspan="2">
			<table class="nobordernopadding">
				<tr>
					<td width="64px" valign="middle">
						<textarea maxlength="224" rows="6" cols="48" name="tax_comment" id="tax_comment" onkeyup="return comment_length(\'tax_comment\', \'remaining_chars\');">'.$c.'</textarea>
					</td>
					<td valign="middle" style="padding-left:32px;" id="remaining_chars">
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			'.$langs->trans('AME_TableColonYear').'
		</td>
		<td colspan="2">
			'.$y.'
		</td>
	</tr>
	<tr>
		<td class="fieldrequired">
			'.$langs->trans('AME_TableColonType').'
		</td>
		<td colspan="2">
			<table class="nobordernopadding">
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('type', AME_ENUM::TAXTYPE_PRODUCTS, $t).'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_Products').'
					</td>
				</tr>
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('type', AME_ENUM::TAXTYPE_SERVICES, $t).'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_Services').'
					</td>
				</tr>
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('type', AME_ENUM::TAXTYPE_BOTH, $t).'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_ProductsAndServices').'
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="fieldrequired">
			'.$this->components->textwithpicto( $langs->trans('AME_PercentageAccuracy'), $langs->trans('AME_PercentageAccuracyHelp')).'
		</td>
		<td colspan="2">
			<table class="nobordernopadding">
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('percentage_accuracy', 2, $a, 'onclick="return setAccuracy(2);"').'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_TwoDecimals').'
					</td>
				</tr>
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('percentage_accuracy', 3, $a, 'onclick="return setAccuracy(3);"').'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_ThreeDecimals').'
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="fieldrequired">
			'.$langs->trans('AME_TableColonValue').'
		</td>
		<td colspan="2">
			<table class="nobordernopadding">
				<tr height="24px">
					<td align="center" width="220">'.$this->components->textwithpicto('', $langs->trans('AME_IntegerButtonsHelp')).'</td>
					<td align="center" width="120">&nbsp;</td>
					<td align="center" width="220">'.$this->components->textwithpicto('', $langs->trans('AME_DecimalButtonsHelp')).'</td>
				</tr>
				<tr height="36px">
					<td align="center" width="220">
						<input type="button" class="button" style="margin:0;" value="-5" name="intMM5" onclick="return intMM(\''.$f_n.'\', 5);">
						<input type="button" class="button" style="margin:0;" value="-1" name="intMM1" onclick="return intMM(\''.$f_n.'\', 1);">
						<input type="button" class="button" style="margin:0;" value="+1" name="intPP1" onclick="return intPP(\''.$f_n.'\', 1);">
						<input type="button" class="button" style="margin:0;" value="+5" name="intPP5" onclick="return intPP(\''.$f_n.'\', 5);">
					</td>
					<td align="center" width="120">
						<input type="text" value="" name="user_value" readonly="readonly" size="6" disabled="disabled"> %
					</td>
					<td align="center" width="320">
						<input type="button" class="button" style="margin:0; visibility:hidden;" value="-50" name="partMM50" onclick="return partMM(\''.$f_n.'\', 50);">
						<input type="button" class="button" style="margin:0;" value="-5" name="partMM5" onclick="return partMM(\''.$f_n.'\', 5);">
						<input type="button" class="button" style="margin:0;" value="-1" name="partMM1" onclick="return partMM(\''.$f_n.'\', 1);">
						<input type="button" class="button" style="margin:0;" value="+1" name="partPP1" onclick="return partPP(\''.$f_n.'\', 1);">
						<input type="button" class="button" style="margin:0;" value="+5" name="partPP5" onclick="return partPP(\''.$f_n.'\', 5);">
						<input type="button" class="button" style="margin:0; visibility:hidden;" value="+50" name="partPP50" onclick="return partPP(\''.$f_n.'\', 50);">
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<div align="center" style="margin-top:16px;">
	<input type="submit" id="reg_button" class="button" value="'.$langs->trans('AME_Register').'">
	&nbsp;<a class="butAction" href="'.$urlback.'">'.$langs->trans('Cancel').'</a>
</div>

</form>
';

/*
	// XXX : rounded values at taxes level ?
	<tr>
		<td class="fieldrequired">
			'.$this->components->textwithpicto($langs->trans('AME_TableColonRound'), $langs->trans('AME_TaxRoundHelp')).'
		</td>
		<td colspan="2">
			<table class="nobordernopadding">
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('roundtype', AME_ENUM::TAXROUND_NONE, $r).'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_TaxRound_None').'
					</td>
				</tr>
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('roundtype', AME_ENUM::TAXROUND_FLOOR, $r).'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_TaxRound_Floor').'
					</td>
				</tr>
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('roundtype', AME_ENUM::TAXROUND_ROUND, $r).'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_TaxRound_Round').'
					</td>
				</tr>
				<tr height="18px">
					<td width="16px" valign="middle">
						'.$this->getInputRadio('roundtype', AME_ENUM::TAXROUND_CEIL, $r).'
					</td>
					<td valign="middle">
						'.$langs->trans('AME_TaxRound_Ceil').'
					</td>
				</tr>
			</table>
		</td>
	</tr>
*/

		$this->printPageFooter();

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function getInputRadio($name, $inputValue, $userValue, $optionalJS=NULL) {
		$checked = ($userValue == $inputValue) ? ' checked="checked"' : '';
		return '<input type="radio" name="'.$name.'" value="'.$inputValue.'"'.$checked.' '.$optionalJS.' />';

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}


	private function addErrorMessage($field) {
		global $langs;

		if( ! is_null($this->mesg) )
			$this->mesg .= '<br />';
		$this->mesg .= $langs->trans('ErrorFieldRequired', $field);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

	private function checkNewValue( &$value, $reset, $error=NULL ) {
		global $langs;

		if( $value === '' ) {
			if( ! is_null($error) )
				$this->addErrorMessage( $langs->trans($error) );
			if( $reset )
				$value = NULL;
		}

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
	}

}

?>
