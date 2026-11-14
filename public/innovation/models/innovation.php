<?php
class Innovation {
    private $id;
    private $titre;
    private $description;
    private $categorie;
    private $image;
    private $statut;

    public function __construct($id, $titre, $description, $categorie, $image, $statut)
    {
        $this->id = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->categorie = $categorie;
        $this->image = $image;
        $this->statut = $statut;
    }

    public function getId(){ return $this->id; }
    public function getTitre(){ return $this->titre; }
    public function getDescription(){ return $this->description; }
    public function getCategorie(){ return $this->categorie; }
    public function getImage(){ return $this->image; }
    public function getStatut(){ return $this->statut; }
}
