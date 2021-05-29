-- ============================================================================
-- Copyright (C) 2021		Open-DSI	  <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================

CREATE TABLE `llx_c_type_transaction`(
    rowid			integer      AUTO_INCREMENT PRIMARY KEY,
    code			varchar(12)  NOT NULL,
    label			varchar(32),
    active          tinyint DEFAULT 1  NOT NULL,
    entity          integer  DEFAULT 1 NOT NULL
)ENGINE=innodb DEFAULT CHARSET=utf8;

ALTER TABLE llx_c_type_transaction ADD UNIQUE INDEX uk_c_type_transaction(code);

INSERT INTO llx_c_type_transaction (code, label, active) values ('STANDARD', 'Standard', 1);