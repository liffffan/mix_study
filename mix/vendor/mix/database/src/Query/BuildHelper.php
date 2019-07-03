<?php

namespace Mix\Database\Query;

/**
 * Class BuildHelper
 * @package Mix\Database\Query
 * @author liu,jian <coder.keda@gmail.com>
 */
class BuildHelper
{

    /**
     * 是否为多个条件
     * @param $where
     * @return bool
     */
    public static function isMulti($where)
    {
        $multi = true;
        foreach ($where as $item) {
            if (!is_array($item)) {
                $multi = false;
                break;
            }
        }
        return $multi;
    }

    /**
     * 构建数据
     * @param array $data
     * @return array
     */
    public static function buildData(array $data)
    {
        $sql    = [];
        $params = [];
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                list($operator, $value) = $item;
                $sql[]        = "`{$key}` =  `{$key}` {$operator} :{$key}";
                $params[$key] = $value;
                continue;
            }
            $sql[]        = "`{$key}` = :{$key}";
            $params[$key] = $item;
        }
        return [implode(', ', $sql), $params];
    }

    /**
     * 构建条件
     * @param array $where
     * @param int $id
     * @return array
     */
    public static function buildWhere(array $where, &$id = null)
    {
        $sql    = '';
        $params = [];
        foreach ($where as $key => $item) {
            $id++;
            $prefix = "__{$id}_";
            $length = count($item);
            if ($length == 2) {
                // 子条件
                if (in_array($item[0], ['or', 'and']) && is_array($item[1])) {
                    list($symbol, $subWhere) = $item;
                    if (count($subWhere) == count($subWhere, 1)) {
                        $subWhere = [$subWhere];
                    }
                    list($subSql, $subParams) = static::buildWhere($subWhere, $id);
                    if (count($subWhere) > 1) {
                        $subSql = "({$subSql})";
                    }
                    $sql    .= " " . strtoupper($symbol) . " {$subSql}";
                    $params = array_merge($params, $subParams);
                }
                // 无值条件
                if (is_string($item[0]) && is_string($item[1])) {
                    list($field, $operator) = $item;
                    $operator = strtoupper($operator);
                    $subSql   = "{$field} {$operator}";
                    $sql      .= " AND {$subSql}";
                    if ($key == 0) {
                        $sql = $subSql;
                    }
                }
            }
            if ($length == 3) {
                // 标准条件 (包含In/NotIn/Between/NotBetween)
                list($field, $operator, $condition) = $item;
                $in      = in_array(strtoupper($operator), ['IN', 'NOT IN']);
                $between = in_array(strtoupper($operator), ['BETWEEN', 'NOT BETWEEN']);
                if (
                    (is_string($field) && is_string($operator) && is_scalar($condition)) ||
                    (is_string($field) && ($in || $between) && is_array($condition))
                ) {

                    $name     = $prefix . str_replace('.', '_', $field);
                    $operator = strtoupper($operator);
                    if (!is_array($condition)) {
                        $subSql        = "{$field} {$operator} :{$name}";
                        $params[$name] = $condition;
                    } else {
                        if ($in) {
                            $subSql        = "{$field} {$operator} (:{$name})";
                            $params[$name] = $condition;
                        }
                        if ($between) {
                            $name1  = $prefix . 's_' . str_replace('.', '_', $field);
                            $name2  = $prefix . 'e_' . str_replace('.', '_', $field);
                            $subSql = "{$field} {$operator} :{$name1} AND :{$name2}";
                            list($condition1, $condition2) = $condition;
                            $params[$name1] = $condition1;
                            $params[$name2] = $condition2;
                        }
                    }
                    $sql .= " AND {$subSql}";
                    if ($key == 0) {
                        $sql = $subSql;
                    }
                }
            }
            if ($length == 4) { // 为了兼容旧版本，保留这项功能
                // Between/NotBetween
                list($field, $operator, $condition1, $condition2) = $item;
                if (
                    is_string($field) &&
                    in_array(strtoupper($operator), ['BETWEEN', 'NOT BETWEEN']) &&
                    is_scalar($condition1) &&
                    is_scalar($condition2)
                ) {
                    $name1    = $prefix . '1_' . str_replace('.', '_', $field);
                    $name2    = $prefix . '2_' . str_replace('.', '_', $field);
                    $operator = strtoupper($operator);
                    $subSql   = "{$field} {$operator} :{$name1} AND :{$name2}";
                    $sql      .= " AND {$subSql}";
                    if ($key == 0) {
                        $sql = $subSql;
                    }
                    $params[$name1] = $condition1;
                    $params[$name2] = $condition2;
                }
            }
        }
        return [$sql, $params];
    }

    /**
     * 构建Join条件
     * @param array $on
     * @return string
     */
    public static function buildJoinOn(array $on)
    {
        $sql = '';
        if (count($on) == count($on, 1)) {
            $on = [$on];
        }
        foreach ($on as $key => $item) {
            if (count($item) == 3) {
                list($field, $operator, $condition) = $item;
                $subSql = "{$field} {$operator} {$condition}";
                $sql    .= " AND {$subSql}";
                if ($key == 0) {
                    $sql = $subSql;
                }
                continue;
            }
            if (count($item) == 2) {
                list($symbol, $subOn) = $item;
                if (!in_array($symbol, ['or', 'and'])) {
                    continue;
                }
                if (count($subOn) == count($subOn, 1)) {
                    $subOn = [$subOn];
                }
                $subSql = static::buildJoinOn($subOn);
                if (count($subOn) > 1) {
                    $subSql = "({$subSql})";
                }
                $sql .= " " . strtoupper($symbol) . " {$subSql}";
            }
        }
        return $sql;
    }

}
