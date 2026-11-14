<?php

namespace Controller;

use Model\User;

class UserController {

    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function createUser($data) {
        return $this->userModel->create($data);
    }

    public function readUsers() {
        return $this->userModel->readAll();
    }

    public function updateUser($id, $data) {
        return $this->userModel->update($id, $data);
    }

    public function deleteUser($id) {
        return $this->userModel->delete($id);
    }
}