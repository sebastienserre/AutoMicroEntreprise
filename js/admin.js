//
//  This file is part of AutoMicroEntreprise Module, a module for Dolibarr.
//  Copyright (C) 2013-2018 Fabrice Delliaux <netbox253@gmail.com>
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
//	\file		automicroent/js/admin.js
//	\ingroup	AutoMicroEntreprise
//	\brief		javascript
//	\author		Fab <netbox253@gmail.com>
//	\version	7.0.1

function twoDigits(d) {
	if( d < 10 ) d = '0' + d;
	return d;
}

function threeDigits(d) {
	if( d >= 10 && d < 100 )
		d = '0' + d;
	else if( d < 10 )
		d = '00' + d;
	return d;
}

function getPercentageAccuracy(accuracy, d) {
	if( accuracy == 2 )
		return twoDigits(d);
	if( accuracy == 3 )
		return threeDigits(d);
}

function buttonsVisibility(F, a) {
	F.partMM50.style.visibility = 'hidden';
	F.partPP50.style.visibility = 'hidden';
	if( a == 3 ) {
		F.partMM50.style.visibility = 'visible';
		F.partPP50.style.visibility = 'visible';
	}
}

function setAccuracy(a) {
	var f = 'register_rate';
	F = window.document.forms[f];
	F.accuracy.value = a;

	buttonsVisibility(F, a);

	F.part_value.value = 0; // reset to 0
	return partMM(f, 1);
}

function updateUserInputValue(f_orm) {
	var v = f_orm.int_value.value;
	v += '.' + f_orm.part_value.value;
	f_orm.user_value.value = v;
	return true;
}

function processRateForm( f_name ) {
	var F = window.document.forms[f_name];
	var p = F.part_value;
	var a = F.accuracy.value;
	p.value = getPercentageAccuracy( a, new Number(p.value) );
	buttonsVisibility(F, a);
	updateUserInputValue( F );
	checkMMValue( p.value, F, 'part' );
	checkPPValue( p.value, F, 'part' );
	checkMMValue( F.int_value.value, F, 'int' );
	checkPPValue( F.int_value.value, F, 'int' );
}

function getMaxValue( n ) {
	var max = 99;
	if( n == 'int' )
		return max;
	var f = 'register_rate';
	F = window.document.forms[f];
	var a = F.accuracy.value;
	if( a == 3 )
		max = 999;
	return max;
}

function checkMMValue(v, f_orm, n) {
	max = getMaxValue( n );
	if( v < 0 ) v = 0;
	if( v < max ) {
		eval('f_orm.'+n+'PP1.disabled = false');
		eval('f_orm.'+n+'PP5.disabled = false');

		if( n == 'part' )
			eval('f_orm.'+n+'PP50.disabled = false');
	}
	if( v == 0 ) {
		eval('f_orm.'+n+'MM5.disabled = true');
		eval('f_orm.'+n+'MM1.disabled = true');
		eval('f_orm.'+n+'MM5.blur()');
		eval('f_orm.'+n+'MM1.blur()');

		if( n == 'part' ) {
			eval('f_orm.'+n+'MM50.disabled = true');
			eval('f_orm.'+n+'MM50.blur()');
		}
	}
	return v;
}

function checkPPValue(v, f_orm, n) {
	max = getMaxValue( n );
	if( v >= max + 1 ) v = max;
	if( v == max ) {
		eval('f_orm.'+n+'PP1.disabled = true');
		eval('f_orm.'+n+'PP5.disabled = true');
		eval('f_orm.'+n+'PP1.blur()');
		eval('f_orm.'+n+'PP5.blur()');

		if( n == 'part' ) {
			eval('f_orm.'+n+'PP50.disabled = true');
			eval('f_orm.'+n+'PP50.blur()');
		}
	}

	if( v > 0 ) {
		eval('f_orm.'+n+'MM5.disabled = false');
		eval('f_orm.'+n+'MM1.disabled = false');

		if( n == 'part' )
			eval('f_orm.'+n+'MM50.disabled = false');
	}
	return v;
}

function partMM(f, step) {
	var f_orm = window.document.forms[f];
	var part = f_orm.part_value;

	var v = new Number(part.value);
	v -= step;

	v = checkMMValue( v, f_orm, 'part' );
	var a = f_orm.accuracy.value;
	part.value = getPercentageAccuracy( a, v );

	return updateUserInputValue(f_orm);
}

function partPP(f, step) {
	var f_orm = window.document.forms[f];
	var part = f_orm.part_value;

	var v = new Number(part.value);
	v += step;

	v = checkPPValue( v, f_orm, 'part' );
	var a = f_orm.accuracy.value;
	part.value = getPercentageAccuracy( a, v );

	return updateUserInputValue(f_orm);
}


function intMM(f, step) {
	var f_orm = window.document.forms[f];
	var integer = f_orm.int_value;

	var v = new Number(integer.value);
	v -= step;

	v = checkMMValue( v, f_orm, 'int' );
	integer.value = v;

	return updateUserInputValue(f_orm);
}

function intPP(f, step) {
	var f_orm = window.document.forms[f];
	var integer = f_orm.int_value;

	var v = new Number(integer.value);
	v += step;

	v = checkPPValue( v, f_orm, 'int' );
	integer.value = v;

	return updateUserInputValue(f_orm);
}

function buttonState(checkboxes_name, submit_button_name) {
	var checkboxes = document.getElementsByName(checkboxes_name);
	if( checkboxes.length > 0) {
		var num = 0;
		for(var i=0; i < checkboxes.length; i++)
			if(checkboxes[i].checked)
				num++;
		var button = document.getElementById(submit_button_name);
		button.disabled = (num == 0) ? true : false;
	}
	return true;
}

function enableRegisterButton() {
	window.document.getElementById('reg_button').disabled=false;
}

function addToOnLoad(o) {
	if( window.addEventListener )
		window.addEventListener('load', o, false);
	else if( window.attachEvent )
		window.attachEvent('onload', o);
	else
		document.addEventListener('load', o, false);
}

