<?php

namespace Core\Database;

use Core\App;
use PDO;

class Connection 
{
    public static function make()
    {
        $config = App::get('config')['database'];
        try {
            
            return new PDO(
                $config['connection'].';dbname='.$config['name'],

                $config['username'],
                
                $config['password'],

                $config['options']
            );
        
        } catch (PDOexception $e) {
        
            die($e->getMessage());
        
        }
                
    }


}
