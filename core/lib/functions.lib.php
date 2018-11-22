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
 *	\file		automicroent/core/lib/functions.lib.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Library of functions used to create module pages
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

function AMEHeader($morejs='') {
	global $langs;

	llxHeader('',$langs->trans('Module451050Name'),'','','','',$morejs,'','');
	print PHP_EOL.PHP_EOL.'<!-- Auto Micro Entreprise Module -->'.PHP_EOL.PHP_EOL;
}

function AMEFooter() {
	print PHP_EOL.PHP_EOL.'<!-- End Auto Micro Entreprise Module -->'.PHP_EOL.PHP_EOL;
	llxFooter();
}

function AMEError($type, $msg) {
	AMEHeader();
	dol_print_error( '', 'Caught '.$type.': '.$msg );
	AMEFooter();
	exit;
}

function AMEPermissionError() {
	global $langs;

	AMEHeader();
	print '<p>'.img_error().' '.$langs->trans('AME_PermissionIssue').'</p>';
	AMEFooter();
}

?>
