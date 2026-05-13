<?php
/**
 * @author Bryan Lee <yeebwn@gmail.com>
 */

namespace Woonysoft\PDO;

abstract class PDOEnabled {

    private $db;
    private $table;

    /**
     * PDOEnabled constructor.
     * @param \PDO $db
     * @param string $table
     */
    function __construct($db, $table) {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * @param $name
     * @return mixed
     */
    abstract protected function onDefaultValue($name);

    /**
     * @param string $name
     * @param mixed $value
     * @return true|array
     */
    abstract protected function onValidate($name, $value);

    function getActivityCategories() {
        $fields = array(
            'ActivityCategory' => 'activityCode',
            'Title' => 'activityAbbr',
            'Body' => 'activityTitle',
            'MinTermHrsJr' => 'hoursJr',
            'MinTermHrsSr' => 'hoursSr',
        );
        $fields = $this->buildSelectList($fields);
        $sql = "SELECT {$fields} FROM tblBHSSPActivityConfig ORDER BY ActivityCategory ASC";
        return $this->query($sql);
    }

    function getInstructors() {
        $fields = array(
            'StaffID' => 'staffId',
            'FirstName' => 'firstName',
            'LastName' => 'lastName',
            'Email3' => 'email',
            'PositionTitle' => 'positionTitle',
            'RoleBOGS' => 'roleBogs',
            'AuthBOGS' => 'authBogs',
        );
        $fields = $this->buildSelectList($fields);
        $sql = "SELECT {$fields} FROM tblStaff WHERE Department IN ('4', '5') AND CurrentStaff='Y' ORDER BY FirstName ASC, LastName ASC";
        return $this->query($sql);
    }

    protected function buildSelectList($fields, $prefix = '') {
        $names = array();
        foreach($fields as $name => $alias) {
            if(is_numeric($name)) {
                $names[] = $prefix ? "{$prefix}.{$alias}" : $alias;
            } else {
                $names[] = $prefix ? "{$prefix}.{$name} {$alias}" : "{$name} {$alias}";
            }
        }
        return implode(',', $names);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return bool
     */
    protected function execute($sql, $params = array()) {
        $db = $this->db;
        $st = $db->prepare($sql);
        $res = $st->execute($params);
        return $res;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return int
     */
    protected function executeAndCount($sql, $params = array()) {
        $db = $this->db;
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    protected function executeInsert($table, $data) {
        $fields = array();
        $placeholders = array();
        $values = array();
        foreach($data as $name => $value) {
            $fields[] = $name;
            $placeholders[] = '?';
            $values[] = $value;
        }
        $fields = implode(',', $fields);
        $placeholders = implode(',', $placeholders);
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $res = $this->execute($sql, $values);
        return $res;
    }

    protected function executeUpdate($table, $data, $conditions) {
        $fields = array();
        $values = array();
        $where = array();
        foreach($data as $name => $value) {
            $fields[] = "{$name}=?";
            $values[] = $value;
        }
        foreach($conditions as $name => $value) {
            $where[] = "{$name}=?";
            $values[] = $value;
        }
        $fields = implode(',', $fields);
        $where = implode(' AND ', $where);
        $sql = "UPDATE {$table} SET {$fields} WHERE {$where}";
        return $this->execute($sql, $values);
    }

    protected function executeDelete($table, $conditions) {
        $values = array();
        $where = array();
        foreach($conditions as $name => $value) {
            $where[] = "{$name}=?";
            $values[] = $value;
        }
        $where = implode(' AND ', $where);
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->execute($sql, $values);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function query($sql, $params = array()) {
        $db = $this->db;
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $sql
     * @param array $params
     * @return array
     * @throws \Exception
     */
    protected function queryOne($sql, $params = array()) {
        $res = $this->query($sql, $params);
        $count = count($res);
        if($count == 1) {
            return $res[0];
        } else if($count == 0) {
            return array();
        } else {
            throw new \Exception("{$count} rows returned. Consider to use PDOEnabled->query(...)");
        }
    }

    protected function find($fields, $conditions, $sorts, $limit, $offset) {

    }

    protected function count($conditions) {

    }

    protected function findOne($fields, $conditions) {

    }

    /**
     * @example
     * $pdoUser = new PDOUser($db);
     * $fields = array('username', 'password', 'email');
     * $rows = array(
     *     array('username' => 'bryan1', 'password' => 'secret', 'email' => 'yeebwn1@gmail.com'),
     *     array('username' => 'bryan2', 'password' => 'secret', 'email' => 'yeebwn2@gmail.com'),
     *     array('username' => 'bryan3', 'password' => 'secret', 'email' => 'yeebwn3@gmail.com'),
     *     array('username' => 'bryan4', 'password' => 'secret', 'email' => 'yeebwn4@gmail.com'),
     * );
     * $pdoUser->insert('users', $fields, $rows);
     * @param array $fields
     * @param array $rows array of row
     * @throws \Exception
     */
    protected function insert($fields, $rows) {
        foreach($rows as $row) {
            $this->validateInput($row, $fields);
        }
        $sql = $this->buildInsert($fields);
        foreach($rows as $row) {
            $this->prepareInput($row, $fields);
            $params = $this->buildParams($row, array(), $fields);
            $this->execute($sql, $params);
        }
    }

    /**
     * @example
     * $pdoUser = new PDOUser($db);
     * $fields = array('username', 'password', 'email');
     * $row = array('username' => 'bryan', 'password' => 'secret', 'email' => 'yeebwn@gmail.com');
     * $pdoUser->insertOne('users', $fields, $row);
     * @param array $fields
     * @param array $row
     * @throws \Exception
     */
    protected function insertOne($fields, $row) {
        $this->validateInput($row, $fields);
        $this->prepareInput($row, $fields);
        $sql = $this->buildInsert($fields);
        $params = $this->buildParams($row, array(), $fields);
        $this->execute($sql, $params);
    }

    /**
     * @param $table
     * @param $keys
     * @param $fields
     * @param $rows
     * @throws \Exception
     */
    protected function update($table, $keys, $fields, $rows) {
        foreach($rows as $row) {
            $this->validateInput($row, $keys);
        }
        foreach($rows as $row) {
            $this->validateInput($row, $fields);
        }
        $sql = $this->buildUpdate($keys, $fields);
        foreach($rows as $row) {
            $this->prepareInput($row, $fields);
            $params = $this->buildParams($row, $keys, $fields);
            $this->execute($sql, $params);
        }
    }

    /**
     * @param $keys
     * @param $fields
     * @param $row
     * @throws \Exception
     */
    protected function updateOne($keys, $fields, $row) {
        $this->validateInput($row, $keys);
        $this->validateInput($row, $fields);
        $this->prepareInput($row, $fields);
        $sql = $this->buildUpdate($keys, $fields);
        $params = $this->buildParams($row, $keys, $fields);
        $this->execute($sql, $params);
    }

    /**
     * @param $keys
     * @param $rows
     * @throws \Exception
     */
    protected function delete($keys, $rows) {
        foreach($rows as $row) {
            $this->validateInput($row, $keys);
        }
        $sql = $this->buildDelete($keys);
        foreach($rows as $row) {
            $params = $this->buildParams($row, $keys, array());
            $this->execute($sql, $params);
        }
    }

    /**
     * @param $keys
     * @param $row
     * @throws \Exception
     */
    protected function deleteOne($keys, $row) {
        $this->validateInput($row, $keys);
        $sql = $this->buildDelete($keys);
        $params = $this->buildParams($row, $keys, array());
        $this->execute($sql, $params);
    }

    /**
     * @param $inputValues
     * @param $fields
     * @throws \Exception
     */
    private function validateInput(&$inputValues, $fields) {
        $errors = array();
        foreach($fields as $name) {
            $result = $this->onValidate($name, $inputValues[$name]);
            if($result !== true) {
                $errors[$name] = $result;
            }
        }
        if(count($errors) > 0) {
            throw new \Exception(implode(PHP_EOL, $errors));
        }
    }

    /**
     * @param $inputValues
     * @param $fields
     * @throws \Exception
     */
    private function prepareInput(&$inputValues, $fields) {
        foreach($fields as $name) {
            if(!isset($inputValues[$name])) {
                $defVal = $this->onDefaultValue($name);
                if($defVal === false) {
                    throw new \Exception("Invalid input value ({$name})");
                }
                $inputValues[$name] = $defVal;
            }
        }
    }

    private function buildParams($values, $keys, $fields) {
        $params = array();
        $names = array_merge($keys, $fields);
        foreach($names as $name) {
            $paramName = ':'.$name;
            $paramValue = $values[$name];
            $params[$paramName] = $paramValue;
        }
        return $params;
    }

    private function buildInsert($fields) {
        $names = array();
        $placeholders = array();
        foreach($fields as $field) {
            $names[] = $field;
            $placeholders[] = ':'.$field;
        }
        $names = implode(',', $names);
        $placeholders = implode(',', $placeholders);
        return "INSERT INTO {$this->table} ({$names}) VALUES ({$placeholders})";
    }

    private function buildUpdate($keys, $fields) {
        $conditions = array();
        $placeholders = array();
        foreach($keys as $key) {
            $placeholders[] = "{$key}=:{$key}";
        }
        foreach($fields as $field) {
            $placeholders[] = "{$field}=:{$field}";
        }
        $conditions = implode('AND', $conditions);
        $placeholders = implode(',', $placeholders);
        return "UPDATE {$this->table} SET {$placeholders} WHERE {$conditions}";
    }

    private function buildDelete($keys) {
        $conditions = array();
        foreach($keys as $key) {
            $placeholders[] = "{$key}=:{$key}";
        }
        $conditions = implode('AND', $conditions);
        return "DELETE FROM {$this->table} WHERE {$conditions}";
    }

}
