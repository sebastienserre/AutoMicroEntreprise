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
 *	\file		automicroent/class/exceptions.class.php
 *	\ingroup	AutoMicroEntreprise
 *	\brief		File defining exceptions
 *	\author		Fab <netbox253@gmail.com>
 *	\version	7.0.1
 */

class DBQueryError extends Exception { }
class DBQueryNoResult extends Exception { }
class DBQueryAlreadyRegistered extends Exception { }
class DBQueryTaxInsertSuccess extends Exception { }
class DBQueryTaxUpdateSuccess extends Exception { }

class AMEDeleteSuccess extends Exception { }
class AMEDeleteFailure extends Exception { }

class AMEWrongQuarter extends Exception { }
class AMEWrongYear extends Exception { }
class AMEYearRedirection extends Exception { }

class AMEUnknownENUM extends Exception { }
?>
