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
 *	\file		automicroent/admin/index.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		Configuration main page
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

require_once dirname(__DIR__).'/class/admin.class.php';
require_once dirname(__DIR__).'/core/lib/functions.lib.php';

$langs->load('automicroent@automicroent');
$langs->load('admin');

$o = NULL;

try {
	if( $user->rights->automicroent->use )
		$o = new adminConfig();
}
catch( DBQueryError $e ) {
	AMEError( 'DBQueryError', $e->getMessage() );
}
catch( AMEYearRedirection $e ) {
	$urlback = $_SERVER['PHP_SELF'].$e->getMessage();
	header('Location: '.$urlback);
	exit;
}


if( $user->rights->automicroent->use ) {
	$morejs = array('/automicroent/js/admin.js','/automicroent/js/index.js');
	AMEHeader($morejs);

	$o->showPage();

	AMEFooter();
}
else {
	AMEPermissionError();
}

$db->close();

?>
