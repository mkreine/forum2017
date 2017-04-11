<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DB\MySQLi;

/**
 * Description of MySQLi
 *
 * @author mkreine
 */
class MySQLi extends \DB\Database {
    
   
    public function __construct() {
        
        global $config;
        
        $this->server           =   $config['db']['server'];
        $this->user             =   $config['db']['user'];
        $this->password         =   $config['db']['password'];
        $this->database         =   $config['db']['dbname'];
        $this->port             =   (int)$config['db']['port'];
        $this->persistent       =   $config['db']['persistent'];
        $this->connect();
    }
    
    /**
     * Соединение с базой данных. Защищенная функция, т.к. вызывается в конструкторе. Проверяет наличие флага
     * постоянного соединения, если он установлен, то оно активируется. В конце происходит выбор базы данных для работы.
     * @return type
     */
    protected function connect() {
        
        if ($this->persistent) {
            
            $this->connection_id    = mysqli_connect('p:'.$this->server, $this->user, $this->password, $this->database, $this->port);
        
            
        }
        else {
            
            $this->connection_id    = mysqli_connect($this->server, $this->user, $this->password, $this->database, $this->port);
        }
        

       $this->chooseDB(); 
       return $this->connection_id;
        
    }
    
    
    public function chooseDB() {
        
        if ($this->dbExists($this->database)) {
            mysqli_select_db($this->connection_id, $this->database);
        }
        
        else {
            return false;
        }
    }
    
    public function query($conn_id, $text) {
        
               
        $this->setQuery($text);
        
        
        $this->query_id     = mysqli_query($conn_id, $text);
        
        return $this->query_id;
    }
    
    public function preanalyze() {
        
        
        while ($line = mysqli_fetch_row($this->query_id)) {
            yield $line;
        }
        
    }
    
    public function getRow() {
        
        if (!$this->query_id) {
            return false;
        }
        
        $row = mysqli_fetch_row($this->query_id);
        return $row;
    }
    
    public function getRowsNumber():int {
        
        if ($this->query_id) {
            return mysqli_num_rows($this->query_id);
        }
        else {
            return false;
        }
    }
    
       
    public function tableExists($db, $table):boolean {
        
        $this->query($this->connection_id, "SHOW TABLES FROM ".$db);
        $tbs = $this->preanalyze();
        $result = false;
        
        foreach ($tbs as $value) {
            if ($table == $value[0]) {
                $result = true;
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * Проверяет физическое наличие на сервере базы данных $db
     * 
     * @param type $db
     * @return boolean
     */
    public function dbExists($db):boolean {
        
        $this->query($this->connection_id, "SHOW DATABASES");
        $dbs = $this->preanalyze();
        $result = false;
        foreach ($dbs as $value) {
            if ($db == $value[0]) {
                $result = true;
                break;
            } 
        }
        
        return $result;
    }
    
    /**
     * Создаёт таблицу с именем $table_name и полями $fields
     * 
     * $fields - это массив с данными:
     * 
     * $fields['name'] - имя поля
     * $fields['type'] - тип поля
     * $fields['modifiers'] - модификаторы поля
     * 
     * @param type $table_name
     * @param array $fields
     * @return \DB\MySQLi_Table
     */
    public function create_table($table_name, array $fields) {
        
        //начало запроса
        $query = "CREATE TABLE ".$table_name ."(";
        $i = 0;
        foreach ($fields as $value) {
            $query .= $value['name']." ".$value['type']." ";
            if (is_array($value['modifiers'])) {
                 $j = 0;
                foreach ($value['modifiers'] as $mods) {
                    $query .= $mods;
                    $j++;
                    if ($j < count($value['modifiers'])) {
                        $query .=" ";
                    }
                }
            }
            
            
            
            $i++;
            if ($i < count($fields)) {
                $query .= ",";
            }
            else {
                $query .= ");";
            }
        }
        
       $this->query($query);
       $errno = mysqli_errno($this->connection_id);
       if ($errno == 0) {
            return new \DB\MySQLi_Table($this, $table_name);
           }
       }
       
       
       public function wasError($conn_id):boolean {
           
           $errno = mysqli_errno($conn_id);
           if ($errno > 0) {
               return true;
           }
           else {
               return false;
           }
       }
       
       public function getError($conn_id):string {
           
           return mysqli_error($conn_id);
       }
       
       public function getLastID($conn_id):int {
           return mysqli_insert_id($conn_id);
       }
     
     
    }
    
