<?php

require_once __DIR__ . '/../models/categorie.php';

class CategorieController
{

    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function addCategorie(Categorie $c)
    {
        $id = $c->getId();
        if (!empty($id) && $id > 0) {
            $sql = "INSERT INTO categorie (id_categorie, nom, description) VALUES (:id, :nom, :description)";
            $db = $this->db;
            $req = $db->prepare($sql);
            $req->bindValue(':id', $id, PDO::PARAM_INT);
            $req->bindValue(':nom', $c->getNom());
            $req->bindValue(':description', $c->getDescription());
        } else {
            $sql = "INSERT INTO categorie (nom, description) VALUES (:nom, :description)";
            $db = $this->db;
            $req = $db->prepare($sql);
            $req->bindValue(':nom', $c->getNom());
            $req->bindValue(':description', $c->getDescription());
        }
        $req->execute();
    }

    public function listCategories()
    {
        $sql = "SELECT * FROM categorie ORDER BY id_categorie DESC";
        $db = $this->db;
        return $db->query($sql)->fetchAll();
    }

    public function deleteCategorie($id)
    {
        $sql = "DELETE FROM categorie WHERE id_categorie = :id";
        $db = $this->db;
        $req = $db->prepare($sql);
        $req->bindValue(':id', $id);
        $req->execute();
    }

    public function getCategorieById($id)
    {
        $sql = "SELECT * FROM categorie WHERE id_categorie = :id";
        $db = $this->db;
        $req = $db->prepare($sql);
        $req->bindValue(':id', $id);
        $req->execute();
        return $req->fetch();
    }

    public function updateCategorie(Categorie $c)
    {
        $sql = "UPDATE categorie SET nom = :nom, description = :description WHERE id_categorie = :id";
        $db = $this->db;
        $req = $db->prepare($sql);
        $req->bindValue(':nom', $c->getNom());
        $req->bindValue(':description', $c->getDescription());
        $req->bindValue(':id', $c->getId());
        $req->execute();
    }
}
