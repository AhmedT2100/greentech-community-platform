<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../models/innovation.php';
require_once __DIR__ . '/../models/innovationModel.php';

class InnovationController
{

    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }

    public function searchInnovations($keyword)
    {
        $model = new InnovationModel();
        return $model->search($keyword);
    }

    public function filterInnovations($categorie, $statut)
    {
        $model = new InnovationModel();
        return $model->filter($categorie, $statut);
    }

    public function sortInnovations($column, $direction = "ASC")
    {
        $model = new InnovationModel();
        return $model->sortBy($column, $direction);
    }

    public function paginateInnovations($page = 1, $limit = 5)
    {
        $model = new InnovationModel();
        $offset = ($page - 1) * $limit;
        return $model->paginate($offset, $limit);
    }

    public function voteInnovation($id, $value)
    {
        $model = new InnovationModel();
        return $model->vote($id, $value);
    }

    // CREATE
    public function addInnovation(Innovation $i)
    {
        try {
            $sql = "INSERT INTO innovation (titre, description, id_categorie, image, statut)
                    VALUES (:titre, :description, :id_categorie, :image, :statut)";

            $stmt = $this->pdo->prepare($sql);
            $idCat = $i->getCategorie();
            if ($idCat === '' || $idCat === null) {
                $idCat = null; // évite d’insérer 0/'' qui casserait la FK
            }
            $stmt->execute([
                ':titre' => $i->getTitre(),
                ':description' => $i->getDescription(),
                ':id_categorie' => $idCat,
                ':image' => $i->getImage(),
                ':statut' => $i->getStatut()
            ]);

            // Return the ID of the newly inserted record
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            die("Erreur lors de l'ajout de l'innovation : " . $e->getMessage());
        }
    }

    // READ ONE
    public function showInnovation($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM innovation WHERE id_innovation = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur lors de la récupération de l'innovation : " . $e->getMessage());
        }
    }

    // READ ALL
    public function listInnovations()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM innovation");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur lors de la récupération des innovations : " . $e->getMessage());
        }
    }

    // UPDATE
    public function updateInnovation(Innovation $i, $id)
    {
        try {
            $sql = "UPDATE innovation SET
                    titre = :titre,
                    description = :description,
                    id_categorie = :id_categorie,
                    image = :image,
                    statut = :statut
                    WHERE id_innovation = :id";

            $stmt = $this->pdo->prepare($sql);
            $idCat = $i->getCategorie();
            if ($idCat === '' || $idCat === null) {
                $idCat = null;
            }
            $stmt->execute([
                ':titre' => $i->getTitre(),
                ':description' => $i->getDescription(),
                ':id_categorie' => $idCat,
                ':image' => $i->getImage(),
                ':statut' => $i->getStatut(),
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            die("Erreur lors de la mise à jour de l'innovation : " . $e->getMessage());
        }
    }

    // DELETE
    public function deleteInnovation($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM innovation WHERE id_innovation = :id");
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            die("Erreur lors de la suppression de l'innovation : " . $e->getMessage());
        }
    }
}
