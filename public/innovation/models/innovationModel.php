<?php
require_once __DIR__ . '/../../config.php';

class InnovationModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function search($keyword) {
        $sql = "SELECT * FROM innovation WHERE titre LIKE :kw OR description LIKE :kw";
        $query = $this->db->prepare($sql);
        $query->execute(['kw' => "%$keyword%"]);
        return $query->fetchAll();
    }

    public function filter($categorie = null, $statut = null) {
        $sql = "SELECT * FROM innovation WHERE 1=1";
        $params = [];

        if ($categorie !== null && $categorie !== "") {
            $sql .= " AND categorie = :cat";
            $params['cat'] = $categorie;
        }
        if ($statut !== null && $statut !== "") {
            $sql .= " AND statut = :st";
            $params['st'] = $statut;
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }

    public function sortBy($column, $direction = "ASC") {
        $allowedColumns = ['titre', 'date_creation', 'votes', 'statut'];
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        if (!in_array($column, $allowedColumns)) {
            return [];
        }
        $sql = "SELECT * FROM innovation ORDER BY $column $direction";
        return $this->db->query($sql)->fetchAll();
    }

    public function paginate($offset, $limit) {
        $sql = "SELECT * FROM innovation LIMIT :offset, :limit";
        $query = $this->db->prepare($sql);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    private function ensureRatingColumns() {
        try {
            // Vérifier et créer rating_total si nécessaire
            $this->db->exec("ALTER TABLE innovation ADD COLUMN rating_total DECIMAL(10,2) DEFAULT 0");
        } catch (PDOException $e) {
            // Colonne existe déjà, ignorer l'erreur
        }
        try {
            // Vérifier et créer rating_count si nécessaire
            $this->db->exec("ALTER TABLE innovation ADD COLUMN rating_count INT DEFAULT 0");
        } catch (PDOException $e) {
            // Colonne existe déjà, ignorer l'erreur
        }
    }
    
    public function vote($id, $stars) {
        // Accepter seulement des valeurs de 1 à 5 étoiles
        if ($stars < 1 || $stars > 5) {
            return false;
        }
        
        // S'assurer que les colonnes existent
        $this->ensureRatingColumns();
        
        // Mettre à jour avec rating_total et rating_count
        try {
            $sql = "UPDATE innovation SET 
                    rating_total = IFNULL(rating_total, 0) + :stars,
                    rating_count = IFNULL(rating_count, 0) + 1
                    WHERE id_innovation = :id";
            $query = $this->db->prepare($sql);
            return $query->execute(['stars' => $stars, 'id' => $id]);
        } catch (PDOException $e) {
            // Si erreur, essayer avec seulement votes (fallback)
            $sql = "UPDATE innovation SET votes = IFNULL(votes, 0) + :stars WHERE id_innovation = :id";
            $query = $this->db->prepare($sql);
            return $query->execute(['stars' => $stars, 'id' => $id]);
        }
    }
    
    public function getRating($id) {
        try {
            $sql = "SELECT rating_total, rating_count FROM innovation WHERE id_innovation = :id";
            $query = $this->db->prepare($sql);
            $query->execute(['id' => $id]);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['rating_count']) && $result['rating_count'] > 0) {
                return round($result['rating_total'] / $result['rating_count'], 1);
            }
        } catch (PDOException $e) {
            // Colonnes n'existent pas encore, retourner 0
        }
        return 0;
    }
}

