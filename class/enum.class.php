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
 *	\file		automicroent/class/enum.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		AutoMicroEntreprise enum class
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */

class AME_ENUM {

	// ---
	const TAXTYPE_PRODUCTS = 0;
	const TAXTYPE_SERVICES = 1;
	const TAXTYPE_BOTH     = 2;

	// ---
	const TAXROUND_NONE  = 0;	// do not round
	const TAXROUND_FLOOR = 1;	// round down to lowest integer
	const TAXROUND_ROUND = 2;	// round to nearest integer
	const TAXROUND_CEIL  = 3;	// round up to highest integer
}

?>
