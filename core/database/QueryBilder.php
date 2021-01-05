<?php

namespace Core\Database;

use Core\Database\Connection;
use PDO;

class QueryBilder
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = Connection::make();
    }

    public function __destruct()
    {
        $this->pdo = null;
    }

    public function insert(String $tableName, Array $arguments)
    {
        $pdo = $this->pdo;
        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $tableName,
            implode(', ', array_keys($arguments)),
            ':' . implode(', :', array_keys($arguments)),
        );
        $statement =  $pdo->prepare($query);
        $statement->execute($arguments);
        $elementId = $pdo->lastInsertId();
        $statement = null;
        return $elementId;
    }

    public function selectWhere(String $tableName, $attributes = [], $where = [])
    {
        $pdo = $this->pdo;
        $query = '';
        $table = $tableName;
        
        if ($attributes) {
            if (!is_array($attributes)) {
    
                $query = "SELECT $attributes FROM $table";
    
            } elseif (is_array($attributes)) {
    
                $attr = implode(', ', $attributes);
                $query = "SELECT $attr FROM $table";
    
            } 
        } else {
            $query = "SELECT * FROM  $table";

        }

        if ($where) {
            $query = $query . " WHERE " . $where[0] . "= '" . $where[1] . "'";
        }

        $statement =  $pdo->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement = null;
        return $result;
    }

    public function selectOneWhere(String $tableName, $attributes = [], $where = [])
    {
        $pdo = $this->pdo;
        $query = '';
        $table = $tableName;
        
        if ($attributes) {
            $query = "SELECT $attributes FROM $table";

        } else {
            $query = "SELECT * FROM  $table";

        }

        if ($where) {
            $query = $query . " WHERE " . $where[0] . "= '" . $where[1] . "'";
        }
        $query = $query . ' LIMIT 1';
        $statement =  $pdo->prepare($query);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        return $result;
    }

    public function deleteWhere(String $tableName, $column, $value)
    {
        $pdo = $this->pdo;
        $query = "DELETE FROM $tableName WHERE $column = '$value' ";
        $statement =  $pdo->prepare($query);
        $statement->execute();
        $statement = null;
    }
    
}