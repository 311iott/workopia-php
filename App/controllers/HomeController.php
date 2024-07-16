<?php

namespace App\Controllers;

use Framework\Database;

class HomeController {

    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    public function index(){
        
        $listings = $this->db->query('select * from listings order by created_at desc limit 6')->fetchAll();

        loadView('home', [
            'listings' => $listings
        ]);

        
    }
}