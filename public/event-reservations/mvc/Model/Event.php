<?php
// mvc/Model/Event.php
// Simple Event model with basic CRUD using PDO

class Event {
    private $db;
    private $table = 'evenements';

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT id_event, titre, description, categorie, date_event, heure_debut, heure_fin, lieu, prix, places_total, places_restantes, COALESCE(profit, 0) as profit FROM {$this->table} ORDER BY date_event ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT id_event, titre, description, categorie, date_event, heure_debut, heure_fin, lieu, prix, places_total, places_restantes, COALESCE(profit, 0) as profit FROM {$this->table} WHERE id_event = :id_event LIMIT 1");
        $stmt->bindParam(':id_event', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data) {
        $query = "INSERT INTO {$this->table} (titre, description, categorie, date_event, heure_debut, heure_fin, lieu, prix, places_total, places_restantes)
                  VALUES (:titre, :description, :categorie, :date_event, :heure_debut, :heure_fin, :lieu, :prix, :places_total, :places_restantes)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':titre', $data['titre']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':categorie', $data['categorie']);
        $stmt->bindParam(':date_event', $data['date_event']);
        $stmt->bindParam(':heure_debut', $data['heure_debut']);
        $stmt->bindParam(':heure_fin', $data['heure_fin']);
        $stmt->bindParam(':lieu', $data['lieu']);
        $stmt->bindParam(':prix', $data['prix']);
        $stmt->bindParam(':places_total', $data['places_total']);
        $stmt->bindParam(':places_restantes', $data['places_total']);
        return $stmt->execute();
    }

    public function update($id, array $data) {
        $query = "UPDATE {$this->table} SET titre = :titre, description = :description, categorie = :categorie, date_event = :date_event,
                  heure_debut = :heure_debut, heure_fin = :heure_fin, lieu = :lieu, prix = :prix, places_total = :places_total, places_restantes = :places_restantes
                  WHERE id_event = :id_event";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':titre', $data['titre']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':categorie', $data['categorie']);
        $stmt->bindParam(':date_event', $data['date_event']);
        $stmt->bindParam(':heure_debut', $data['heure_debut']);
        $stmt->bindParam(':heure_fin', $data['heure_fin']);
        $stmt->bindParam(':lieu', $data['lieu']);
        $stmt->bindParam(':prix', $data['prix']);
        $stmt->bindParam(':places_total', $data['places_total']);
        $stmt->bindParam(':places_restantes', $data['places_restantes']);
        $stmt->bindParam(':id_event', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id_event = :id_event");
        $stmt->bindParam(':id_event', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

?>
