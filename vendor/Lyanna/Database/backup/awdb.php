<?php
/* AWDB.php - AW's Database Module
 * Copyright (C) 2003-2014 by Daniel Hawton
 *
 * AWDB is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; either version 3.0 of
 * the License, or (at your option) any later version.
 *
 * AWDB is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA
 */
namespace Lyanna\Database;

class awdb
{
    public $__version = "0.22";

    public function
    db_build(&$db)
    {
        $db = mysql_connect($this->__databaseServer, $this->__databaseUser, $this->__databasePass);
        mysql_select_db($this->__databaseName);

        if ($db) { return 1; }
        return 0;
    }

    public function
    db_num_rows($res)
    {
        if (!$res)
        {
            throwWarning("awdb","db_num_rows","Expected valid database result resource");
        }

        return mysql_num_rows($res);
    }

    public function
    db_affected_rows($db)
    {
        return mysql_affected_rows($db);
    }

    public function
    db_fetch_assoc($res)
    {
        if (!$res)
        {
            throwWarning("awdb","db_fetch_assoc","Expected valid database result resource");
        }

        if (mysql_num_rows($res) == 0)
        {
            return null;
        }

        return mysql_fetch_assoc($res);
    }

    public function
    db_done(&$db)
    {
        mysql_close($db);

        if (!isset($db)) { return 1; }

        throw new Exception("Database connection persisting after done call");
    }

    public function
    db_execute(&$db, $query)
    {
        mysql_query($query, $db) or die("database execution failed: " . mysql_error());

        return mysql_affected_rows($db);
    }

    public function
    db_fetchone(&$db, &$row, $cols, $table, $filter)
    {
        $iBuilt = false;
        $query = "SELECT $cols FROM $table";
        if (isset($filter)) { $query .= " WHERE $filter"; }
        $query .= " LIMIT 1";

        if (!isset($db)) { $this->db_build($db); $iBuilt = true; }

        if ($db == 0) { throw new Exception("Attempted to build database connection, but failed."); }
        if ($this->db_query($db, $res, $query)) {
            $row = mysql_fetch_assoc($res);
        } else { if ($iBuilt) { $this->db_done($db); } return 0; }
        if ($iBuilt) { $this->db_done($db); }
        return 1;
    }

    public function
    db_query(&$db, &$res, $query)
    {
        $res = mysql_query($query, $db) or die("Database query failed: " . mysql_error());

        if ($res && mysql_num_rows($res) > 0) { return 1; }
        return 0;
    }

    public function
    db_safe($str)
    {
        $search=array("\\","\0","\n","\r","\x1a","'",'"');
        $replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
        return str_replace($search,$replace,$str);
    }
}