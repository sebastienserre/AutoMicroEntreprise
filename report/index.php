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
 *	\file		automicroent/report/index.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Report table page
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */


if( is_file('../../main.inc.php') )		/* htdocs */
	define('NM_DOL_ROOT_DIR', '../..');
elseif( is_file('../../../main.inc.php') )	/* htdocs/custom */
	define('NM_DOL_ROOT_DIR', '../../..');
else						/* symlink */
	define('NM_DOL_ROOT_DIR', '../../dolibarr/htdocs');

require NM_DOL_ROOT_DIR.'/main.inc.php';

require_once dirname(__DIR__).'/class/report.class.php';
require_once dirname(__DIR__).'/core/lib/functions.lib.php';

$langs->load('automicroent@automicroent');

$o = NULL;

try {
	if( $user->rights->automicroent->use )
		$o = new reportTable();
}
catch( DBQueryError $e ) {
	AMEError( 'DBQueryError', $e->getMessage() );
}

if( $user->rights->automicroent->use ) {
	AMEHeader();

	if( $o->checkForResults() )
		$o->showPage();
	else
		$o->showNoResultPage();

	AMEFooter();
}
else {
	AMEPermissionError();
}

$db->close();

?>
