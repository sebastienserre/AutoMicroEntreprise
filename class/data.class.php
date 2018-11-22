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
 *	\file		automicroent/class/data.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Implements data manager object for report table
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */


require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

require_once __DIR__.'/exceptions.class.php';
require_once __DIR__.'/categories.class.php';

define('DEBUG', 0);

function d_print($object) {
	if( ! DEBUG )
		return;

	if( gettype( $object ) !== 'string' ) {
		print '<pre>';
		print_r( $object );
		print '</pre>';
		return;
	}

	print $object.'<br />'.PHP_EOL;
}

class reportData {

	private $data = array();
	private $taxes = NULL;
	private $details = array();

	private $virtualProductsContainer = array();
	private $virtualPaymentsContainer = array();
	private $ref=1;

	function __construct($year_start, $nbofyear, &$taxesObj) {
		$years = array();

		for($i=0; $i<$nbofyear; $i++)
			$years[] = $year_start++;

		$this->execQueriesAndSortResults($years);

		$this->taxes = $taxesObj;
	}

	function __destruct() {
	}

	/*
	 *	Public methods
	 *
	 */

	/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

	public function getQuarterAmount($y, $m) {
		$sum = 0;
		for( $i=0; $i < 3; $i++ ) {
			$sum += $this->getMonthProductsAmount($y, $m+$i);
			$sum += $this->getMonthServicesAmount($y, $m+$i);
		}
		if( $sum == 0 ) return NULL;
		$sum = $this->getMoneyString( $sum );
		return $sum;
	}

	public function getQuarterProductsAmount($y, $m) {
		$sum = 0;
		for( $i=0; $i < 3; $i++ )
			$sum += $this->getMonthProductsAmount($y, $m+$i);
		if( $sum == 0 ) return NULL;
		$sum = $this->getMoneyString( $sum );
		return $sum;
	}

	public function getQuarterServicesAmount($y, $m) {
		$sum = 0;
		for( $i=0; $i < 3; $i++ )
			$sum += $this->getMonthServicesAmount($y, $m+$i);
		if( $sum == 0 ) return NULL;
		$sum = $this->getMoneyString( $sum );
		return $sum;
	}

	public function getYearProductsAmount($y) {
		$y = $this->getStringIndexFromInt($y);
		$sum = 0;
		if( ! isset( $this->data[$y] ) ) return NULL;
		foreach( $this->data[$y] as $month )
			$sum += $month['P'];
		$sum = $this->getMoneyString( $sum );
		return $sum;
	}

	public function getYearServicesAmount($y) {
		$y = $this->getStringIndexFromInt($y);
		$sum = 0;
		if( ! isset( $this->data[$y] ) ) return NULL;
		foreach( $this->data[$y] as $month )
			$sum += $month['S'];
		$sum = $this->getMoneyString( $sum );
		return $sum;
	}

	public function getYearAmount($y) {
		$y = $this->getStringIndexFromInt($y);
		$sum = 0;
		if( ! isset( $this->data[$y] ) ) return NULL;
		foreach( $this->data[$y] as $month )
			$sum += $month['P'] + $month['S'];
		$sum = $this->getMoneyString( $sum );
		return $sum;
	}

	/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

	// Unlike others similar functions, this one returns an array instead of a float value.
	// The array contains estimated loads for each level of taxes for a specific month.
	// For a specific level, if loads are 0, the corresponding value in the array will be NULL.
	public function getMonthLoads($y, $m) {
		$l = NULL;
		$ret = array();
		$cats = new categories($y);
		foreach($cats->get() as $c) {
			$l  = $c->getLevel();
			$v = $this->getMonthLoadsByLevel($y, $m, $l);
			$v = $c->getRoundedValue($v);
			if( $v == 0  ) $v = NULL;
			$ret[$l] = $v;
		}
		unset($cats, $c);
		d_print( "Month Loads by level :" );
		d_print( $ret );
		return $ret;
	}

	public function getQuarterLoads($y, $m) {
		$l = 0;
		$cats = new categories($y);
		foreach($cats->get() as $c) {
			$v = $this->getQuarterLoadsByLevel($y, $m, $c->getLevel());
			$v = $c->getRoundedValue($v);
			d_print( "rounded sum=".$v );
			$l += $v;
		}
		unset($cats, $c);
		d_print("quarter loads = $l");
		if( $l == 0 ) return NULL;
		return $l;
	}

	public function getYearLoads($y) {
		$quarters = array(1, 4, 7, 10);
		$sum = 0;
		foreach( $quarters as $q)
			$sum += $this->getQuarterLoads($y, $q);
		if( $sum == 0 ) return NULL;
		return $sum;
	}

	/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

	public function getVPNumber() {
		return sizeof($this->virtualProductsContainer);
	}

	public function getVPLastRef() {
		return ($this->ref - 1);
	}

	public function getVPProductsString($ref) {
		$s = 0;
		foreach( $this->virtualProductsContainer as $p) {
			if( $p->getVPRef() == $ref ) {
				if( $p->getVPType() == 'P' ) {
					$s = $p->getVPPrice();
					break;
				}
			}
		}
		return $this->getMoneyString($s);
	}

	public function getVPServicesString($ref) {
		$s = 0;
		foreach( $this->virtualProductsContainer as $p) {
			if( $p->getVPRef() == $ref ) {
				if( $p->getVPType() == 'S' ) {
					$s = $p->getVPPrice();
					break;
				}
			}
		}
		return $this->getMoneyString($s);
	}

	public function getVPInvoiceID($ref) {
		foreach( $this->virtualProductsContainer as $p) {
			if( $p->getVPRef() == $ref ) {
				return $p->getVPInvoiceID();
			}
		}
	}

	public function getVPInvoiceAmount($ref) {
		foreach( $this->virtualProductsContainer as $p) {
			if( $p->getVPRef() == $ref ) {
				return $this->getMoneyString($p->getVPInvoiceAmount());
			}
		}
	}

	public function getVPPaymentAmount($ref) {
		foreach( $this->virtualProductsContainer as $p) {
			if( $p->getVPRef() == $ref ) {
				return $this->getMoneyString($p->getVPPaymentAmount());
			}
		}
	}

	public function getVPInvoiceNumber($ref) {
		foreach( $this->virtualProductsContainer as $p) {
			if( $p->getVPRef() == $ref ) {
				return $p->getVPInvoiceNumber();
			}
		}
	}

	public function getVPInvoiceDateString($ref) {
		foreach( $this->virtualProductsContainer as $p) {
			if( $p->getVPRef() == $ref ) {
				return $p->getVPDateString();
			}
		}
	}

	/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

	public function getVPRefStringFromDate($y, $m) {
		$y = $this->getStringIndexFromInt($y);
		$m = $this->getStringIndexFromInt($m);
		$s = '';
		$r = 0;
		foreach( $this->virtualProductsContainer as $p) {
			if( $p->getVPYear() == $y and $p->getVPMonth() == $m
				and $r != $p->getVPRef() ) {
				$r = $p->getVPRef();
				$s .= '<sup>('.$r.')</sup>';
			}
		}
		return $s;
	}

	public function getMonthProductsAmount($y, $m) {
		$y = $this->getStringIndexFromInt($y);
		$m = $this->getStringIndexFromInt($m);
		if( ! isset( $this->data[$y][$m] ) )
			return NULL;
		return $this->getMoneyString( $this->data[$y][$m]['P'] );
	}

	public function getMonthServicesAmount($y, $m) {
		$y = $this->getStringIndexFromInt($y);
		$m = $this->getStringIndexFromInt($m);
		if( ! isset( $this->data[$y][$m] ) )
			return NULL;
		return $this->getMoneyString( $this->data[$y][$m]['S'] );
	}

	public function getMonthAmount($y, $m) {
		$y = $this->getStringIndexFromInt($y);
		$m = $this->getStringIndexFromInt($m);
		if( ! isset( $this->data[$y][$m] ) )
			return NULL;
		return $this->getMoneyString( ($this->data[$y][$m]['P'] + $this->data[$y][$m]['S']) );
	}

	/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

	/*
	 *	Private methods
	 *
	 */

	private function getMonthLoadsByLevel($year, $month, $level) {
		$q_p_l = $this->getMonthProductsAmount($year, $month);
		$q_s_l = $this->getMonthServicesAmount($year, $month);

		$loads = 0;

		$year = (string)$year;
		$level = (string)$level;
		$match_array = $this->taxes->getMatchArray($year, $level);
		d_print('---');
		foreach( $match_array as $i ) {
			$r_t = $this->taxes->getTaxType($i);
			if( $r_t == 0 )
				$sum = &$q_p_l;
			if( $r_t == 1 )
				$sum = &$q_s_l;
			if( $r_t == 2 )
				$sum = $q_p_l + $q_s_l;
			$r = $this->taxes->getTaxRate($i);
			$load = $sum * $r / 100;
			d_print( "y=$year m=$month l=$level r=$r % === $load" );
			$loads += $load;
		}
		d_print( "sum=".$loads );
		return $loads;
	}

	private function getQuarterLoadsByLevel($year, $month, $level) {
		$q_p_l = $this->getQuarterProductsAmount($year, $month);
		$q_s_l = $this->getQuarterServicesAmount($year, $month);

		$loads = 0;

		$year = (string)$year;
		$level = (string)$level;
		$match_array = $this->taxes->getMatchArray($year, $level);
		d_print('---');
		foreach( $match_array as $i ) {
			$r_t = $this->taxes->getTaxType($i);
			if( $r_t == 0 )
				$sum = &$q_p_l;
			if( $r_t == 1 )
				$sum = &$q_s_l;
			if( $r_t == 2 )
				$sum = $q_p_l + $q_s_l;
			$r = $this->taxes->getTaxRate($i);
			$load = $sum * $r / 100;
			d_print( "y=$year m=$month l=$level r=$r % === $load" );
			$loads += $load;
		}
		d_print( "sum=".$loads );
		return $loads;
	}

	private function getMoneyString($value) {
		return sprintf('%01.2f', $value);
	}

	private function getStringIndexFromInt($m) {
		if( is_string($m) ) $m = intval($m);
		if($m < 10) $m = strval('0'.$m);
		return strval($m);
	}

	private function execQueriesAndSortResults($years) {
		global $db, $langs;

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

		$sql =  "SELECT p.rowid AS p_id, date_format(p.datep,'%Y') AS p_year, date_format(p.datep,'%m') AS p_month, p.amount AS p_amount, ";
		$sql .= "pf.fk_paiement AS pf_p_id, pf.fk_facture AS pf_f_id, pf.amount AS pf_amount ";
		$sql .= "FROM ".MAIN_DB_PREFIX."paiement AS p, ".MAIN_DB_PREFIX."paiement_facture AS pf ";
		$sql .= "WHERE pf.fk_paiement = p.rowid AND p.entity IN (".getEntity('user',1).") AND (";
		$i = 0;
		while( $i < sizeof($years) ) {
			if( $i > 0 ) $sql .= ' OR';
			$sql .= " date_format(p.datep,'%Y') = '".$years[$i]."'";
			$i++;
		}
		$sql .= " ) ORDER BY p_year, p_month, p_id, pf_f_id ASC ";

		/* exec */
		$result = $db->query( $sql );
		if( $result === FALSE )
			throw new DBQueryError( $db->lasterror() );

		$num_payments = $db->num_rows( $result );
		if( $num_payments == 0 )
			throw new DBQueryNoResult( $langs->trans('AME_NoResultForThisPeriod') );

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

		$sql2 = "SELECT fd.fk_facture AS fk_id, fd.total_ttc AS product_price, fd.product_type, f.facnumber ";
		$sql2 .= "FROM ".MAIN_DB_PREFIX."facturedet AS fd, ".MAIN_DB_PREFIX."facture AS f ";
		$sql2 .= "WHERE f.rowid = fd.fk_facture AND f.entity IN (".getEntity('user',1).") AND fd.fk_facture IN (";

		$payments = array();

		$i = 0;
		while($i < $num_payments)
		{
			if($i>0) $sql2 .= ', ';
			$obj = $db->fetch_object( $result );
			$obj->index = array();
			$payments[] = $obj;
			$sql2 .= $obj->pf_f_id;
			$i++;
		}
		$sql2 .= ') ORDER BY fk_id ASC';

		/* exec */
		$result = $db->query( $sql2 );
		if( $result === FALSE )
			throw new DBQueryError( $db->lasterror() );

		$num_details = $db->num_rows( $result );

		$i = 0;
		while($i < $num_details)
		{
			$this->details[] = $db->fetch_object( $result );
			$i++;
		}

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

		$newobj = NULL;

		$i = 0;
		while($i < $num_payments)
		{
			$j = 0;
			while($j < $num_details ) {
				if( $this->details[$j]->fk_id == $payments[$i]->pf_f_id )
					$payments[$i]->index[] = $j;
				$j++;
			}
			$i++;
		}

		//d_print($this->details);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

/*
		stdClass Object
		(
			[p_id]		=> 1			paiement_id		---------\
			[p_year]	=> 2011			paiement_year		--------- \	llx_paiement
			[p_month]	=> 09			paiement_month		--------- /
			[p_amount]	=> 52.00000000		paiement_amount		---------/
			[pf_p_id]	=> 1			paiement_facture_paiement_id		-----\
			[pf_f_id]	=> 3			paiement_facture_facture_id		----- | llx_paiement_facture
			[pf_amount]	=> 52			paiement_facture_amount			-----/
			[index] => Array
				(
					[0] => 3
					[1] => 4
					[2] => 5
					[3] => 6
					[4] => 7
				)
		)
*/

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

		d_print('<br />*** | starting payments handling');

		$partialPayments = array();

		foreach( $payments as $p ) {
			$year	= $p->p_year;
			$month	= $p->p_month;

			if( ! is_array( $this->data[$year] ) )
				$this->data[$year] = array();
			if( ! is_array( $this->data[$year][$month] ) )
				$this->data[$year][$month] = array();

			$d_out = &$this->data[$year][$month];

			if( ! isset( $d_out['P'] ) )
				$d_out['P'] = 0;
			if( ! isset( $d_out['S'] ) )
				$d_out['S'] = 0;

			$invoiceAmount = 0;

			foreach($p->index as $i)
				$invoiceAmount += $this->details[$i]->product_price;

			$invoiceAmount = round($invoiceAmount, 2);

			// full payment (with extra ?)
			if( $p->pf_amount >= $invoiceAmount ) {
				$extra = $p->pf_amount - $invoiceAmount;

				$out = 'p_id: '.$p->p_id.' | ';
				if( $p->p_amount == $p->pf_amount )
					$out .= 'facture payée ';
				else if( $p->p_amount > $p->pf_amount )
					$out .= 'un seul paiement pour plusieurs factures, facture payée ';
				else d_print('*** FIXME : p_amount < pf_amount');
				$out.= ($extra > 0) ? 'avec extra : '.$extra : ':)';

				d_print( $out );

				foreach($p->index as $i) {
					$d = &$this->details[$i];
					if($d->product_type == 0)
						$d_out['P'] += $d->product_price;
					else if($d->product_type == 1)
						$d_out['S'] += $d->product_price;
				}
				$d_out['S'] += $extra;
			}
			// partial payment
			else {
				d_print('p_id: '.$p->p_id.' | paiement partiel');
				$partialPayments[] = $p;
			}
		}

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

		d_print('<br />*** | starting partial payments handling');

		foreach( $partialPayments as $p ) {
			//d_print($p);
			foreach( $this->virtualPaymentsContainer as $payment ) {
				if( $payment->checkInvoiceID( $p->pf_f_id ) ) {
					$payment->add( $p );
					// jump to next partial payment
					continue 2;
				}
			}
			$this->virtualPaymentsContainer[] = new VirtualPayment($p, $this->details);
		}
		unset($p, $payment);

		d_print('<br />*** | starting virtual payments handling');

		foreach( $this->virtualPaymentsContainer as $p ) {
			//d_print( $p );
			$year	= $p->getYear();
			$month	= $p->getMonth();
			$facID	= $p->getInvoiceID();

			if( $p->checkIfPayed() ) {
				$d_out = &$this->data[$year][$month];

				// increase data from products prices
				$d_out['P'] += $p->getInvoiceProductsAmount();
				$d_out['S'] += $p->getInvoiceServicesAmount();

				$s = 'fk_id: '.$facID.' | facture payée ';
				$s .= ($p->getExtra() > 0) ? 'avec extra : '.$p->getExtra() : ':)';
				d_print( $s );

				$d_out['S'] += $p->getExtra();
			}
			else {
				d_print('fk_id: '.$facID.' | facture payée partiellement');

				$i_amount = $p->getInvoiceAmount();
				$p_amount = $p->getPaymentsAmount();
				$productsAmount = $p->getInvoiceProductsAmount();
				$ref = $this->ref++;
				$facNumber = $p->getInvoiceNumber();


				// no products, creating a virtual service with full payment amount
				if( $productsAmount == 0 ) {
					$o = new VirtualProduct(1, $p_amount, $year, $month, $facID, $facNumber, $i_amount, $p_amount, $ref);
					$this->virtualProductsContainer[] = $o;
					continue;
				}

				$remainder = $p_amount;

				// trying to pay real products first
				if( $productsAmount > 0 ) {
					$o = NULL;
					if( ($remainder - $productsAmount) <= 0 )
						$o = new VirtualProduct(0, $remainder, $year, $month, $facID, $facNumber, $i_amount, $p_amount, $ref);
					else
						$o = new VirtualProduct(0, $productsAmount, $year, $month, $facID, $facNumber, $i_amount, $p_amount, $ref);
					$this->virtualProductsContainer[] = $o;
					$remainder -= $productsAmount;
				}

				// last virtual service with remaining payment
				if($remainder > 0) {
					$o = new VirtualProduct(1, $remainder, $year, $month, $facID, $facNumber, $i_amount, $p_amount, $ref);
					$this->virtualProductsContainer[] = $o;
				}
			}
		}

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

		d_print('<br />*** | starting virtual products handling');
		//d_print($this->virtualProductsContainer);

		foreach($this->virtualProductsContainer as $vp ) {
			$y = $vp->getVPYear();
			$m = $vp->getVPMonth();
			$t = $vp->getVPType();
			d_print( "ajout [$y] [$m] [$t] += ".$vp->getVPPrice() );
			$this->data[$y][$m][$t] += $vp->getVPPrice();
		}

		//d_print($this->data);

		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */
		/* --- --- --- --- --- --- --- --- --- --- --- --- --- */

	}

}

class VirtualPayment {

	private $partialPayments = array();
	private $invoiceNumber = NULL;
	private $invoiceAmount = 0;
	private $invoiceProductsAmount = 0;
	private $invoiceServicesAmount = 0;
	private $paymentsAmount = 0;
	private $extra = 0;

	function __construct( $p, $details_array ) {
		d_print( 'Building new VirtualPayment object ...' );

		foreach($p->index as $i) {
			$d = &$details_array[$i];

			if( $d->product_type == 0 )
				$this->invoiceProductsAmount += $d->product_price;
			if( $d->product_type == 1 )
				$this->invoiceServicesAmount += $d->product_price;
			$this->invoiceAmount += $d->product_price;
			$this->invoiceNumber = $d->facnumber;
		}
		$this->invoiceAmount = round($this->invoiceAmount, 2);
		$this->invoiceProductsAmount = round($this->invoiceProductsAmount, 2);
		$this->invoiceServicesAmount = round($this->invoiceServicesAmount, 2);

		d_print( "fac_ID: $p->pf_f_id amount: $this->invoiceAmount year: $p->p_year month: $p->p_month" );

		$this->add( $p );
	}

	function __destruct() {
	}

	public function checkInvoiceID( $id ) {
		if( sizeof($this->partialPayments) == 0 )
			return false;
		return ( $this->partialPayments[0]->pf_f_id == $id );
	}

	public function add( $p ) {
		//d_print( $p );
		d_print( 'Adding payment. p_ID: '.$p->pf_p_id.' Value : '.$p->pf_amount );

		$this->partialPayments[] = $p;

		$this->paymentsAmount += $p->pf_amount;
		$this->paymentsAmount = round($this->paymentsAmount, 2);

		$this->extra = $this->paymentsAmount - $this->invoiceAmount;
	}

	public function getPaymentsAmount() {
		return $this->paymentsAmount;
	}

	public function getYear() {
		return $this->partialPayments[0]->p_year;
	}

	public function getMonth() {
		return $this->partialPayments[0]->p_month;
	}

	public function getInvoiceID() {
		return $this->partialPayments[0]->pf_f_id;
	}

	public function getInvoiceNumber() {
		return $this->invoiceNumber;
	}

	public function getInvoiceAmount() {
		return $this->invoiceAmount;
	}

	public function getInvoiceProductsAmount() {
		return $this->invoiceProductsAmount;
	}

	public function getInvoiceServicesAmount() {
		return $this->invoiceServicesAmount;
	}

	public function getExtra() {
		return $this->extra;
	}

	public function checkIfPayed() {
		return ( $this->paymentsAmount >= $this->invoiceAmount );
	}

}

class VirtualProduct {

	private $p_type;	// product type. 0 --> product	1 --> service
	private $p_price;	// product price
	private $p_year;	// product year
	private $p_month;	// product month
	private $f_id;		// invoice ID
	private $f_num;		// invoice number
	private $i_amount;	// invoice amount
	private $p_amount;	// payment amount
	private $ref;		// reference

	function __construct($t, $v_price, $y, $m, $facID, $facNum, $i_amount, $p_amount, $r) {
		d_print( 'Building new VirtualProduct object ...' );
		$this->p_type = $t;
		$this->p_price = round($v_price, 2); // virtual price
		$this->p_year = $y;
		$this->p_month = $m;
		$this->f_id = $facID;
		$this->f_num = $facNum;
		$this->i_amount = $i_amount;
		$this->p_amount = $p_amount;
		$this->ref = $r;
		d_print( ' fac_ID: '.$f.' type: '.$this->getVPType().' price: '.$v_price.' year: '.$y.' month: '.$m );
	}

	function __destruct() {
	}

	public function getVPType() {
		if( $this->p_type == 0 ) return 'P';
		if( $this->p_type == 1 ) return 'S';
	}

	public function getVPPrice() {
		return $this->p_price;
	}

	public function getVPPaymentAmount() {
		return $this->p_amount;
	}

	public function getVPInvoiceAmount() {
		return $this->i_amount;
	}

	public function getVPDateString() {
		return $this->p_month.' / '.$this->p_year;
	}

	public function getVPYear() {
		return $this->p_year;
	}

	public function getVPMonth() {
		return $this->p_month;
	}

	public function getVPInvoiceID() {
		return $this->f_id;
	}

	public function getVPInvoiceNumber() {
		return $this->f_num;
	}

	public function getVPRef() {
		return $this->ref;
	}

}

?>
