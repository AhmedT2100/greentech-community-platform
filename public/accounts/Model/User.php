<?php

namespace Model;

use PDO;

class User {

    private $db;

    public function __construct() {
        $this->db = (new \Database())->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (:nom, :email, :mot_de_passe, :role)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($data);
    }

    public function readAll() {
        $query = "SELECT * FROM utilisateur";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE utilisateur SET nom = :nom, email = :email, role = :role WHERE id_user = :id";
        $stmt = $this->db->prepare($query);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function delete($id) {
        $query = "DELETE FROM utilisateur WHERE id_user = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute(['id' => $id]);
    }
}