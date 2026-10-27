<?php

/*
----------------------------------
 ------  Created: 102626   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait DatabaseBrowse
{
    public function browseTables()
    {
        $tables  = [];
        $defined = get_defined_constants(true);
        $user    = $defined['user'] ?? [];

        foreach ($user as $name => $value) {
            if (!str_ends_with(strval($name), '_TABLE') || str_starts_with(strval($name), 'MYSQLI_')) {
                continue;
            }
            $table = trim(strval($value));
            if ($table != '') {
                $tables[$table] = true;
            }
        }

        $list = array_keys($tables);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $list;
    }

    public function browseTablesSummary()
    {
        $summary = [];

        foreach ($this->browseTables() as $table) {
            $escaped = str_replace('`', '``', $table);
            $columns = 0;
            $colsRes = $this->mysqli_query('SHOW COLUMNS FROM `' . $escaped . '`');
            if ($colsRes) {
                while ($this->mysqli_fetchAssoc($colsRes)) {
                    $columns++;
                }
            }

            $rows     = 0;
            $countRes = $this->mysqli_query('SELECT COUNT(*) AS total FROM `' . $escaped . '`');
            if ($countRes) {
                $countRow = $this->mysqli_fetchAssoc($countRes);
                $rows     = max(0, intval($countRow['total'] ?? 0));
            }

            $size      = 0;
            $statusRes = $this->mysqli_query("SHOW TABLE STATUS WHERE `Name` = '" . $this->prepare($table) . "'");
            if ($statusRes) {
                $status = $this->mysqli_fetchAssoc($statusRes);
                if ($status) {
                    $size = max(0, intval($status['Data_length'] ?? 0) + intval($status['Index_length'] ?? 0));
                }
            }

            $summary[] = [
                'table'   => $table,
                'columns' => $columns,
                'rows'    => $rows,
                'size'    => $size,
            ];
        }

        return $summary;
    }

    public function browseTableAllowed($table)
    {
        $table = trim(strval($table));
        if ($table == '') {
            return false;
        }

        foreach ($this->browseTables() as $allowed) {
            if ($allowed == $table) {
                return true;
            }
        }

        return false;
    }

    public function formatBrowseSchema($schema)
    {
        $schema = trim(str_replace(["\r\n", "\r"], "\n", strval($schema)));
        if ($schema == '') {
            return $schema;
        }

        $flat = preg_replace('/\s+/', ' ', $schema);
        if (!preg_match('/^(CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:`[^`]+`|[^\s(]+))\s*\((.*)\)\s*(.*)$/is', $flat, $matches)) {
            return $schema;
        }

        $header  = trim($matches[1]);
        $body    = trim($matches[2]);
        $footer  = trim($matches[3]);
        $parts   = [];
        $depth   = 0;
        $current = '';
        $length  = strlen($body);

        for ($i = 0; $i < $length; $i++) {
            $ch = $body[$i];
            if ($ch == '(') {
                $depth++;
                $current .= $ch;
            } else if ($ch == ')') {
                $depth--;
                $current .= $ch;
            } else if ($ch == ',' && $depth == 0) {
                $part = trim($current);
                if ($part != '') {
                    $parts[] = $part;
                }
                $current = '';
            } else {
                $current .= $ch;
            }
        }

        $part = trim($current);
        if ($part != '') {
            $parts[] = $part;
        }

        $lines = [$header . ' ('];
        $last  = count($parts) - 1;
        foreach ($parts as $index => $part) {
            $lines[] = '  ' . $part . ($index < $last ? ',' : '');
        }
        $lines[] = ')' . ($footer != '' ? ' ' . $footer : '');

        return implode("\n", $lines);
    }

    public function browseTableSchema($table)
    {
        $table = trim(strval($table));
        if (!$this->browseTableAllowed($table)) {
            return [
                'error'   => true,
                'message' => 'That table cannot be browsed',
            ];
        }

        $escaped   = str_replace('`', '``', $table);
        $schemaRes = $this->mysqli_query('SHOW CREATE TABLE `' . $escaped . '`');
        $schemaRow = $schemaRes ? $this->mysqli_fetchAssoc($schemaRes) : null;
        if (!$schemaRow) {
            return [
                'error'   => true,
                'message' => $this->mysqli_error() ?: 'Could not load the table schema',
            ];
        }

        return [
            'error'  => false,
            'table'  => $table,
            'schema' => $this->formatBrowseSchema(strval($schemaRow['Create Table'] ?? '(schema unavailable)')),
        ];
    }

    public function browseTablePage($table, $page = 1, $perPage = 25)
    {
        $table   = trim(strval($table));
        $page    = max(1, intval($page));
        $perPage = max(1, intval($perPage));

        if (!$this->browseTableAllowed($table)) {
            return [
                'error'   => true,
                'message' => 'That table cannot be browsed',
            ];
        }

        $escaped = str_replace('`', '``', $table);
        $columns = [];
        $orderBy = '';
        $colsRes = $this->mysqli_query('SHOW COLUMNS FROM `' . $escaped . '`');
        if (!$colsRes) {
            return [
                'error'   => true,
                'message' => $this->mysqli_error() ?: 'Could not browse that table',
            ];
        }

        while ($col = $this->mysqli_fetchAssoc($colsRes)) {
            $field = strval($col['Field'] ?? '');
            if ($field == '') {
                continue;
            }
            $columns[] = $field;
            if ($orderBy == '' && strtolower($field) == 'id') {
                $orderBy = $field;
            }
        }

        if ($orderBy == '' && $columns) {
            $orderBy = $columns[0];
        }

        $countRes = $this->mysqli_query('SELECT COUNT(*) AS total FROM `' . $escaped . '`');
        $countRow = $countRes ? $this->mysqli_fetchAssoc($countRes) : null;
        $total    = max(0, intval($countRow['total'] ?? 0));
        $pages    = max(1, intval(ceil($total / $perPage)));
        if ($page > $pages) {
            $page = $pages;
        }

        $offset  = ($page - 1) * $perPage;
        $dataSql = 'SELECT * FROM `' . $escaped . '`';
        if ($orderBy != '') {
            $orderEsc = str_replace('`', '``', $orderBy);
            $dataSql .= ' ORDER BY `' . $orderEsc . '` ASC';
        }
        $dataSql .= ' LIMIT ' . intval($perPage) . ' OFFSET ' . intval($offset);

        $rows    = [];
        $dataRes = $this->mysqli_query($dataSql);
        if ($dataRes) {
            while ($row = $this->mysqli_fetchAssoc($dataRes)) {
                if (!$columns) {
                    $columns = array_keys($row);
                }
                $rows[] = $row;
            }
        } else if ($this->mysqli_error()) {
            return [
                'error'   => true,
                'message' => $this->mysqli_error() ?: 'Could not browse that table',
            ];
        }

        return [
            'error'   => false,
            'table'   => $table,
            'sql'     => $dataSql,
            'columns' => $columns,
            'rows'    => $rows,
            'page'    => $page,
            'perPage' => $perPage,
            'total'   => $total,
            'pages'   => $pages,
        ];
    }

    public function browseRunQuery($sql, $maxRows = 500)
    {
        $sql = trim(strval($sql));
        if ($sql == '') {
            return [
                'error'   => true,
                'message' => 'Enter a query first',
            ];
        }

        $sql = rtrim($sql, " \t\n\r\0\x0B;");
        if (str_contains($sql, ';')) {
            return [
                'error'   => true,
                'message' => 'Only one statement is allowed',
            ];
        }

        $maxRows = max(1, intval($maxRows));
        $res     = $this->mysqli_query($sql);
        if (!$res) {
            return [
                'error'   => true,
                'message' => $this->mysqli_error() ?: 'Query failed',
            ];
        }

        if (!($res instanceof mysqli_result)) {
            return [
                'error'    => false,
                'sql'      => $sql,
                'columns'  => [],
                'rows'     => [],
                'total'    => 0,
                'capped'   => false,
                'affected' => max(0, intval($this->mysqli_affectedRows())),
            ];
        }

        $columns = [];
        $rows    = [];
        $total   = 0;
        $capped  = false;
        while ($row = $this->mysqli_fetchAssoc($res)) {
            $total++;
            if ($total > $maxRows) {
                $capped = true;
                break;
            }
            if (!$columns) {
                $columns = array_keys($row);
            }
            $rows[] = $row;
        }

        return [
            'error'    => false,
            'sql'      => $sql,
            'columns'  => $columns,
            'rows'     => $rows,
            'total'    => count($rows),
            'capped'   => $capped,
            'maxRows'  => $maxRows,
            'affected' => null,
        ];
    }
}
