<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;
use Framework\Session;
use Framework\Authorization;

class ListingController {

    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    

    public function index(){
        
        $listings = $this->db->query('select * from listings order by created_at desc')->fetchAll();

        loadView('listings/index', [
            'listings' => $listings
        ]);

        
    }

    public function create(){
        loadView('listings/create');
    }
    /**
     * Show a single listing
     * 
     * @param array $params
     * @return void
     */

    public function show($params) {
        $id = $params['id'] ?? '';

        $params = [
                'id' => $id
        ];

        $listing = $this->db->query('select * from listings where id = :id', $params)->fetch();

        //Check if listing exists
        if (!$listing) {
            ErrorController::notFound('Listing not found');
            return;
        }

        loadView('listings/show', [
            'listing' => $listing
        ]);

        
    }
    /**
     * Store data in the database
     * @return void
     */
    public function store() {
        
        $allowedFields = [
            'title', 'description', 'salary', 'tags', 'company', 'address', 'city', 'state', 'email', 'phone', 'requirements', 'benefits'];

        $newListingData = array_intersect_key($_POST, array_flip($allowedFields));

        $newListingData['user_id'] = Session::get('user')['id'];

        $newListingData = array_map('sanitize', $newListingData);

        $requiredFields = ['title', 'description', 'salary', 'email', 'city', 'state'];

        $errors = [];

        foreach ($requiredFields as $field) {
            if(empty($newListingData[$field]) || !Validation::string($newListingData[$field])){
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        
        if (!empty($errors)) {
            //reload view with errors
            loadView('listings/create', [
                'errors' => $errors,
                'listing' => $newListingData
            ]);
        }else {
            //Submit data

        $fields = [];

        foreach ($newListingData as $field => $value) {
                $fields[] = $field;
        }

        $fields = implode(', ', $fields);
        
        $values = [];

        foreach ($newListingData as $field => $value) {
            //convert empty strings to null
            if ($value === ''){
                $newListingData[$field] = null;
            }
            $values[] = ':' . $field;
        }
            $values = implode(', ', $values);
            $query = "insert into listings ({$fields}) values ({$values})";
            $this->db->query($query, $newListingData);
            Session::setFlashMessage('success_message', 'Listing created successfully');
            redirect('/listings');
        }
    }
    /**
     * Delete a listing
     * 
     * @param array $params
     * @return void
     * 
     */
    public function destroy($params) {
        $id = $params['id'];

        $params = [
            'id' => $id
        ];

        $listing = $this->db->query('select * from listings where id = :id', $params)->fetch();
        //Check if listing exists
        if(!$listing) {
            ErrorController::notFound('Listing not found');
            return;
        }
        //Authorization

        if (!Authorization::isOwner($listing->user_id)) {
            Session::setFlashMessage('error_message', 'You are not authorized to delete this listing');
            return redirect('/listings/' . $listing->id);
        }
        $this->db->query('delete from listings where id = :id', $params);

        //Set flash message
        Session::setFlashMessage('success_message', 'Listing deleted successfully');
        
        redirect('/listings');
    }
    /**
     * Show the listing edit form
     * 
     * @param array $params
     * @return void
     */
    public function edit($params) {
        
       
        
        $id = $params['id'] ?? '';

        $params = [
                'id' => $id
        ];

        $listing = $this->db->query('select * from listings where id = :id', $params)->fetch();

        //Check if listing exists
        if (!$listing) {
            ErrorController::notFound('Listing not found');
            return;
        }
        //Authorization
        if (!Authorization::isOwner($listing->user_id)) {
            Session::setFlashMessage('error_message', 'You are not authorized to update this listing');
            return redirect('/listings/' . $listing->id);
        }
        loadView('listings/edit', [
            'listing' => $listing
        ]);
    }
    /**
     * Update a listing
     * 
     * @param array $params
     * @return void
     */
    public function update($params) {
        $id = $params['id'] ?? '';

        $params = [
                'id' => $id
        ];

        $listing = $this->db->query('select * from listings where id = :id', $params)->fetch();

        //Check if listing exists
        if (!$listing) {
            ErrorController::notFound('Listing not found');
            return;
        }
        //Authorization

        if (!Authorization::isOwner($listing->user_id)) {
            Session::setFlashMessage('error_message', 'You are not authorized to update this listing');
            return redirect('/listings/' . $listing->id);
        }
        $allowedFields = [
            'title', 'description', 'salary', 'tags', 'company', 'address', 'city', 'state', 'email', 'phone', 'requirements', 'benefits'];

        $updateValues = [];

        $updateValues = array_intersect_key($_POST, array_flip($allowedFields));

        $updateValues = array_map('sanitize', $updateValues);

        $requiredFields = ['title', 'description', 'salary', 'email', 'city', 'state'];

        $errors = [];

        foreach($requiredFields as $field){
            if(empty($updateValues[$field]) || !Validation::string($updateValues[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        if (!empty($errors)) {
            loadView('listings/edit', [
                'listing' => $listing,
                'errors' => $errors
            ]);
            exit;
        }else {
            //Submit to database
           $updateFields = [];

           foreach(array_keys($updateValues) as $field) {
            $updateFields[] = "{$field} = :{$field}";
           }
           $updateFields = implode(', ', $updateFields);
           
           $updateQuery = "update listings set $updateFields where id = :id";
           $updateValues['id'] = $id;
           $this->db->query($updateQuery, $updateValues);

           Session::setFlashMessage('success_message', 'Listing Updated');
        

           redirect('/listings/' . $id);
          
        }
        
    }
}