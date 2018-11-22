-- ===================================================================
-- This file is part of AutoMicroEntreprise Module, a module for Dolibarr.
-- Copyright (C) 2013-2018 Fabrice Delliaux <netbox253@gmail.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, version 3 of the License.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_ame_rates
(
  rowid			integer		AUTO_INCREMENT PRIMARY KEY,
  year			smallint	UNSIGNED NOT NULL,
  quarter		tinyint		UNSIGNED DEFAULT 0 NOT NULL,
  type			tinyint		UNSIGNED NOT NULL,
  first			tinyint		UNSIGNED DEFAULT 0 NOT NULL,
  second		tinyint		UNSIGNED DEFAULT 0 NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_ame_rates MODIFY COLUMN first SMALLINT DEFAULT 0 NOT NULL;
ALTER TABLE llx_ame_rates MODIFY COLUMN second SMALLINT DEFAULT 0 NOT NULL;

ALTER TABLE llx_ame_rates ADD COLUMN accuracy tinyint UNSIGNED NOT NULL DEFAULT 2 AFTER type;
ALTER TABLE llx_ame_rates ADD COLUMN name varchar(48) NOT NULL DEFAULT 'AME_UPDATE' AFTER rowid;
ALTER TABLE llx_ame_rates ADD COLUMN comment varchar(255) NULL AFTER name;
ALTER TABLE llx_ame_rates ADD COLUMN level tinyint UNSIGNED NOT NULL DEFAULT 1 AFTER comment;

-- XXX : rounded values at taxes level ?
--ALTER TABLE llx_ame_rates ADD COLUMN round tinyint UNSIGNED NOT NULL DEFAULT 1 AFTER second;

