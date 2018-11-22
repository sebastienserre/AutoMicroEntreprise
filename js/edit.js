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
//	\file		automicroent/js/edit.js
//	\ingroup	AutoMicroEntreprise
//	\brief		javascript
//	\author		Fab <netbox253@gmail.com>
//	\version	7.0.1

function comment_length(input_name, output_name) {
	var input_field = window.document.getElementById(input_name);
	var remaining_chars = input_field.maxLength - input_field.value.length;
	string = ' caractères restants';
	if( remaining_chars < 2 )
		string = ' caractère restant';
	window.document.getElementById(output_name).innerHTML = remaining_chars + string;
	return true;
}

function onLoadUpdates() {
	processRateForm( 'register_rate' );
	comment_length('tax_comment', 'remaining_chars');
}

addToOnLoad( onLoadUpdates );

