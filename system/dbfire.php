<?php
/**
 * Cashpe — Firestore-backed compatibility layer for the original PHP code.
 *
 * Every original sql_query()/sql_fetch_array()/sql_num_rows() call keeps working
 * exactly as before. A small SQL subset (SELECT / INSERT / UPDATE / DELETE,
 * WHERE with AND / OR / || / BETWEEN / LIKE / CONCAT, ORDER BY and LIMIT) is
 * translated into Firestore document operations. This means not one line of
 * the payment, settlement, UPI-status or admin business logic had to change.
 *
 * Storage backend:
 *   - Firebase Firestore when credentials are configured (Render production)
 *   - local JSON store when they are not (instant local / Docker demo)
 */

/* ------------------------------------------------------------------ *
 * Result object — behaves like the mysqli_result helpers used by the app
 * ------------------------------------------------------------------ */
class CashpeResult
{
    public $rows = array();
    public $insert_id = 0;
    public $affected_rows = 0;
    private $pointer = 0;

    public function __construct($rows = array(), $insertId = 0, $affected = 0)
    {
        $this->rows = array_values($rows);
        $this->insert_id = $insertId;
        $this->affected_rows = $affected;
    }

    public function fetch()
    {
        if (!isset($this->rows[$this->pointer])) {
            return false;
        }
        $row = $this->rows[$this->pointer++];
        $row = array_values($row) + $row; // numeric keys first, then named keys
        return $row;
    }

    public function count()
    {
        return count($this->rows);
    }
}

/* ------------------------------------------------------------------ *
 * Public API used throughout the application
 * ------------------------------------------------------------------ */
if (!function_exists('sql_query')) {
    function sql_query($sql)
    {
        try {
            return cashpe_exec_sql($sql);
        } catch (Throwable $e) {
            error_log('[Cashpe DB] ' . $e->getMessage());
            return false;
        }
    }
}
if (!function_exists('sql_fetch_array')) {
    function sql_fetch_array($data)
    {
        return $data instanceof CashpeResult ? $data->fetch() : false;
    }
}
if (!function_exists('sql_num_rows')) {
    function sql_num_rows($data)
    {
        return $data instanceof CashpeResult ? $data->count() : 0;
    }
}
if (!function_exists('sql_insert_id')) {
    function sql_insert_id()
    {
        return cashpe_last_insert_id();
    }
}
if (!function_exists('real_escape_string')) {
    function real_escape_string($str)
    {
        return $str;
    }
}
if (!function_exists('str_escape')) {
    function str_escape($value)
    {
        return $value;
    }
}
/* Legacy direct call in activePlan.php — safe on PHP 8 too. */
if (!function_exists('mysqli_fetch_assoc')) {
    function mysqli_fetch_assoc($result)
    {
        $row = $result instanceof CashpeResult ? $result->fetch() : false;
        if (!$row) {
            return null;
        }
        foreach (array_keys($row) as $key) {
            if (is_int($key)) {
                unset($row[$key]);
            }
        }
        return $row;
    }
}

/* ------------------------------------------------------------------ *
 * Connection, cache and low level storage
 * ------------------------------------------------------------------ */
function cashpe_last_insert_id($value = null)
{
    static $id = 0;
    if ($value !== null) {
        $id = (int) $value;
    }
    return $id;
}

function &cashpe_cache($table = null)
{
    static $cache = array();
    if ($table === null) {
        return $cache;
    }
    if (!isset($cache[$table])) {
        if (CashpeFirebase::enabled()) {
            $docs = CashpeFirebase::listDocuments($table);
            $rows = array();
            foreach ($docs as $id => $fields) {
                if (!isset($fields['id']) || $fields['id'] === '') { $fields['id'] = (string) $id; }
                $fields['__id'] = $id;
                $rows[] = $fields;
            }
        } else {
            // Never silently fall back to ephemeral local storage with partial Firebase configuration.
            if (getenv('FIREBASE_PROJECT_ID') || getenv('FIREBASE_CLIENT_EMAIL') || getenv('FIREBASE_PRIVATE_KEY') || getenv('FIREBASE_SERVICE_ACCOUNT') || getenv('FIREBASE_SERVICE_ACCOUNT_PATH') || getenv('FIRESTORE_EMULATOR_HOST')) {
                throw new RuntimeException('Incomplete Firebase configuration');
            }
            $rows = CashpeLocalStore::rows($table);
        }
        usort($rows, function ($a, $b) {
            $left = (int) (isset($a['id']) ? $a['id'] : 0);
            $right = (int) (isset($b['id']) ? $b['id'] : 0);
            return $left == $right ? 0 : ($left < $right ? -1 : 1);
        });
        $cache[$table] = $rows;
    }
    return $cache[$table];
}

function cashpe_flush_cache()
{
    $cache = &cashpe_cache();
    $cache = array();
}

function cashpe_commit($writes)
{
    if (!$writes) {
        return true;
    }
    if (CashpeFirebase::enabled()) {
        CashpeFirebase::commit($writes);
        // A later SELECT in the same request must observe successful writes.
        cashpe_flush_cache();
        return true;
    }
    $byTable = array();
    foreach ($writes as $write) {
        $byTable[$write['collection']][] = $write;
    }
    foreach ($byTable as $table => $tableWrites) {
        $rows = cashpe_cache($table);
        foreach ($tableWrites as $write) {
            if ($write['op'] === 'delete') {
                foreach ($rows as $i => $row) {
                    if ((string) (isset($row['__id']) ? $row['__id'] : (isset($row['id']) ? $row['id'] : '')) === (string) $write['id']) {
                        unset($rows[$i]);
                    }
                }
            } else {
                $found = false;
                foreach ($rows as $i => $row) {
                    if ((string) (isset($row['__id']) ? $row['__id'] : (isset($row['id']) ? $row['id'] : '')) === (string) $write['id']) {
                        $rows[$i] = $write['fields'] + array('__id' => $write['id']);
                        $found = true;
                    }
                }
                if (!$found) {
                    $fields = $write['fields'];
                    $fields['__id'] = $write['id'];
                    $rows[] = $fields;
                }
            }
        }
        $rows = array_values($rows);
        CashpeLocalStore::write($table, $rows);
        $cache = &cashpe_cache();
        $cache[$table] = $rows;
    }
    return true;
}

/* ------------------------------------------------------------------ *
 * SQL parsing helpers
 * ------------------------------------------------------------------ */
function cashpe_unquote($value)
{
    $value = trim($value);
    $len = strlen($value);
    if ($len >= 2 && (($value[0] === "'" && $value[$len - 1] === "'") || ($value[0] === '"' && $value[$len - 1] === '"'))) {
        $inner = substr($value, 1, -1);
        return str_replace(array("\\'", '\\"', "\\\\"), array("'", '"', '\\'), $inner);
    }
    return $value;
}

/** Split a list of values or assignments on top-level commas. */
function cashpe_split_commas($text)
{
    $out = array();
    $buf = '';
    $quote = null;
    $depth = 0;
    $len = strlen($text);
    for ($i = 0; $i < $len; $i++) {
        $char = $text[$i];
        if ($quote) {
            $buf .= $char;
            if ($char === '\\' && $i + 1 < $len) {
                $buf .= $text[++$i];
            } elseif ($char === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($char === "'" || $char === '"') {
            $quote = $char;
            $buf .= $char;
            continue;
        }
        if ($char === '(') {
            $depth++;
        } elseif ($char === ')') {
            $depth--;
        }
        if ($char === ',' && $depth === 0) {
            $out[] = trim($buf);
            $buf = '';
            continue;
        }
        $buf .= $char;
    }
    if (trim($buf) !== '') {
        $out[] = trim($buf);
    }
    return $out;
}

/** Split a boolean expression on a keyword, ignoring quotes and parentheses. */
function cashpe_split_keyword($expr, $keywords)
{
    $parts = array();
    $buf = '';
    $quote = null;
    $depth = 0;
    $len = strlen($expr);
    for ($i = 0; $i < $len; $i++) {
        $char = $expr[$i];
        if ($quote) {
            $buf .= $char;
            if ($char === '\\' && $i + 1 < $len) {
                $buf .= $expr[++$i];
            } elseif ($char === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($char === "'" || $char === '"') {
            $quote = $char;
            $buf .= $char;
            continue;
        }
        if ($char === '(') {
            $depth++;
            $buf .= $char;
            continue;
        }
        if ($char === ')') {
            $depth--;
            $buf .= $char;
            continue;
        }
        if ($depth === 0 && preg_match('/\s/', $char)) {
            $matched = null;
            foreach ($keywords as $keyword) {
                $klen = strlen($keyword);
                if (strtoupper(substr($expr, $i + 1, $klen)) === $keyword
                    && preg_match('/\s/', substr($expr, $i + 1 + $klen, 1))) {
                    $matched = $klen;
                    break;
                }
            }
            if ($matched) {
                if (trim($buf) !== '') {
                    $parts[] = trim($buf);
                }
                $buf = '';
                $i += $matched;
                continue;
            }
        }
        $buf .= $char;
    }
    if (trim($buf) !== '') {
        $parts[] = trim($buf);
    }
    return $parts;
}

function cashpe_like($value, $pattern)
{
    $pattern = str_replace(array('%', '_'), array('@@CPWILD@@', '@@CPONE@@'), $pattern);
    $regex = preg_quote($pattern, '/');
    $regex = str_replace(array('@@CPWILD@@', '@@CPONE@@'), array('.*', '.'), $regex);
    return (bool) preg_match('/^' . $regex . '$/is', (string) $value);
}

function cashpe_eval_condition($condition, $row)
{
    $condition = trim($condition);
    if ($condition === '') {
        return true;
    }
    // BETWEEN conditions were replaced with placeholders by cashpe_filter().
    if (preg_match('/^__(CPB\d+)__$/', $condition, $m) && isset($GLOBALS['__cashpe_between'][$m[0]])) {
        list($field, $low, $high) = $GLOBALS['__cashpe_between'][$m[0]];
        $field = trim($field, '`');
        $value = (string) (isset($row[$field]) ? $row[$field] : '');
        return strcmp($value, $low) >= 0 && strcmp($value, $high) <= 0;
    }
    // CONCAT(a, b) LIKE '%needle%'
    if (preg_match('/^CONCAT\s*\((.*?)\)\s+LIKE\s+(.+)$/is', $condition, $m)) {
        $value = '';
        foreach (cashpe_split_commas($m[1]) as $column) {
            $value .= (string) (isset($row[trim($column, '` ')]) ? $row[trim($column, '` ')] : '');
        }
        return cashpe_like($value, cashpe_unquote($m[2]));
    }
    if (preg_match('/^`?(\w+)`?\s+LIKE\s+(.+)$/is', $condition, $m)) {
        return cashpe_like((isset($row[$m[1]]) ? $row[$m[1]] : ''), cashpe_unquote($m[2]));
    }
    if (preg_match('/^`?(\w+)`?\s*(<>|!=|>=|<=|=|>|<)\s*(.*)$/s', $condition, $m)) {
        $left = (string) ((isset($row[$m[1]]) ? $row[$m[1]] : ''));
        $right = cashpe_unquote($m[3]);
        switch ($m[2]) {
            case '=':
                return $left === $right;
            case '<>':
            case '!=':
                return $left !== $right;
            case '>':
                return is_numeric($left) && is_numeric($right) ? (float) $left > (float) $right : strcmp($left, $right) > 0;
            case '<':
                return is_numeric($left) && is_numeric($right) ? (float) $left < (float) $right : strcmp($left, $right) < 0;
            case '>=':
                return is_numeric($left) && is_numeric($right) ? (float) $left >= (float) $right : strcmp($left, $right) >= 0;
            case '<=':
                return is_numeric($left) && is_numeric($right) ? (float) $left <= (float) $right : strcmp($left, $right) <= 0;
        }
    }
    return false;
}

function cashpe_filter($rows, $where)
{
    $where = trim($where);
    if ($where === '' || strtolower($where) === '1') {
        return $rows;
    }
    $GLOBALS['__cashpe_between'] = array();
    $where = preg_replace_callback(
        '/(`?\w+`?)\s+between\s+(\'[^\']*\'|"[^"]*"|-?\d+(?:\.\d+)?)\s+AND\s+(\'[^\']*\'|"[^"]*"|-?\d+(?:\.\d+)?)/i',
        function ($m) {
            $key = '__CPB' . count($GLOBALS['__cashpe_between']) . '__';
            $GLOBALS['__cashpe_between'][$key] = array($m[1], cashpe_unquote($m[2]), cashpe_unquote($m[3]));
            return $key;
        }, $where);

    $groups = cashpe_split_keyword($where, array('OR', '||'));
    $matched = array();
    foreach ($rows as $row) {
        foreach ($groups as $group) {
            $conditions = cashpe_split_keyword($group, array('AND'));
            $ok = true;
            foreach ($conditions as $condition) {
                if (!cashpe_eval_condition($condition, $row)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $matched[] = $row;
                break;
            }
        }
    }
    return $matched;
}

/* ------------------------------------------------------------------ *
 * Statement execution
 * ------------------------------------------------------------------ */
function cashpe_exec_sql($sql)
{
    $sql = trim($sql);
    $sql = rtrim($sql, "; \t\n\r\0\x0B");
    if ($sql === '') {
        return false;
    }
    if (preg_match('/^SELECT\s+/i', $sql)) {
        return cashpe_select($sql);
    }
    if (preg_match('/^INSERT\s+INTO\s+/i', $sql)) {
        return cashpe_insert($sql);
    }
    if (preg_match('/^UPDATE\s+/i', $sql)) {
        return cashpe_update($sql);
    }
    if (preg_match('/^DELETE\s+FROM\s+/i', $sql)) {
        return cashpe_delete($sql);
    }
    return false;
}

function cashpe_select($sql)
{
    $rest = preg_replace('/^SELECT\s+/i', '', $sql);
    $limit = null;
    if (preg_match('/\s+LIMIT\s+(\d+)\s*$/i', $rest, $m)) {
        $limit = (int) $m[1];
        $rest = substr($rest, 0, strlen($rest) - strlen($m[0]));
    }
    $orderField = null;
    $orderDesc = false;
    if (preg_match('/\s+ORDER\s+BY\s+`?(\w+)`?(?:\s+(ASC|DESC))?\s*$/i', $rest, $m)) {
        $orderField = $m[1];
        $orderDesc = isset($m[2]) && strtoupper($m[2]) === 'DESC';
        $rest = substr($rest, 0, strlen($rest) - strlen($m[0]));
    }
    if (!preg_match('/^(.+?)\s+FROM\s+`?([A-Za-z_]\w*)`?(?:\s+WHERE\s*([\s\S]*))?$/i', $rest, $m)) {
        return new CashpeResult();
    }
    $columns = trim($m[1]);
    $table = $m[2];
    $where = isset($m[3]) ? $m[3] : '';
    $rows = cashpe_filter(cashpe_cache($table), $where);

    if (preg_match('/^sum\s*\(\s*`?(\w+)`?\s*\)$/i', $columns, $sum)) {
        $total = 0;
        foreach ($rows as $row) {
            $total += (float) (isset($row[$sum[1]]) ? $row[$sum[1]] : 0);
        }
        $key = 'sum(' . $sum[1] . ')';
        return new CashpeResult(array(array($total, $key => $total)));
    }
    if (preg_match('/^count\s*\(\s*\*\s*\)$/i', $columns)) {
        return new CashpeResult(array(array(count($rows), 'count(*)' => count($rows))));
    }
    if ($orderField !== null) {
        usort($rows, function ($a, $b) use ($orderField, $orderDesc) {
            $left = (isset($a[$orderField]) ? $a[$orderField] : '');
            $right = (isset($b[$orderField]) ? $b[$orderField] : '');
            if (is_numeric($left) && is_numeric($right)) {
                $cmp = (float) $left == (float) $right ? 0 : ((float) $left < (float) $right ? -1 : 1);
            } else {
                $cmp = strcmp((string) $left, (string) $right);
            }
            return $orderDesc ? -$cmp : $cmp;
        });
    }
    if ($limit !== null) {
        $rows = array_slice($rows, 0, $limit);
    }
    return new CashpeResult($rows);
}

function cashpe_insert($sql)
{
    if (!preg_match('/^INSERT\s+INTO\s+`?([A-Za-z_]\w*)`?\s*\((.*?)\)\s*VALUES\s*\((.*)\)$/is', $sql, $m)) {
        return false;
    }
    $table = $m[1];
    $columns = array_map(function ($c) {
        return trim($c, '` ');
    }, cashpe_split_commas($m[2]));
    $values = cashpe_split_commas($m[3]);
    if (count($columns) !== count($values)) {
        error_log('[Cashpe DB] INSERT column/value mismatch: ' . $sql);
        return false;
    }
    $row = array();
    foreach ($columns as $i => $column) {
        $row[$column] = cashpe_unquote($values[$i]);
    }
    $rows = &cashpe_cache($table);
    if (!isset($row['id']) || $row['id'] === '') {
        $max = 0;
        foreach ($rows as $existing) {
            $max = max($max, (int) (isset($existing['id']) ? $existing['id'] : 0));
        }
        $row['id'] = (string) ($max + 1);
    }
    $id = (string) $row['id'];
    $stored = $row;
    unset($stored['__id']);
    cashpe_commit(array(array('op' => 'set', 'collection' => $table, 'id' => $id, 'fields' => $stored)));
    cashpe_last_insert_id($id);
    return new CashpeResult(array($row), (int) $id, 1);
}

function cashpe_update($sql)
{
    if (!preg_match('/^UPDATE\s+`?([A-Za-z_]\w*)`?\s+SET\s+(.*?)(?:\s+WHERE\s*([\s\S]*))?$/i', $sql, $m)) {
        return false;
    }
    $table = $m[1];
    $assignments = array();
    foreach (cashpe_split_commas($m[2]) as $assignment) {
        if (preg_match('/^`?(\w+)`?\s*=\s*(.*)$/s', $assignment, $pair)) {
            $assignments[$pair[1]] = cashpe_unquote($pair[2]);
        }
    }
    $rows = cashpe_filter(cashpe_cache($table), isset($m[3]) ? $m[3] : '');
    $writes = array();
    $updated = array();
    foreach ($rows as $row) {
        $id = (string) (isset($row['__id']) ? $row['__id'] : $row['id']);
        $fields = $row;
        unset($fields['__id']);
        foreach ($assignments as $key => $value) {
            $fields[$key] = $value;
        }
        $writes[] = array('op' => 'set', 'collection' => $table, 'id' => $id, 'fields' => $fields);
        $updated[] = $fields;
    }
    cashpe_commit($writes);
    return new CashpeResult($updated, 0, count($updated));
}

function cashpe_delete($sql)
{
    if (!preg_match('/^DELETE\s+FROM\s+`?([A-Za-z_]\w*)`?(?:\s+WHERE\s*([\s\S]*))?$/i', $sql, $m)) {
        return false;
    }
    $table = $m[1];
    $rows = cashpe_filter(cashpe_cache($table), isset($m[2]) ? $m[2] : '');
    $writes = array();
    foreach ($rows as $row) {
        $writes[] = array('op' => 'delete', 'collection' => $table, 'id' => (string) (isset($row['__id']) ? $row['__id'] : $row['id']));
    }
    cashpe_commit($writes);
    return new CashpeResult($rows, 0, count($rows));
}
