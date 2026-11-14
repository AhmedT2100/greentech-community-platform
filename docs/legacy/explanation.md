# Explication du projet MVC CRUD Utilisateurs

## Structure et rôle de chaque fichier

- **config.php** : Configure la connexion à la base de données avec PDO.
- **Model/User.php** : Modèle qui gère toutes les opérations SQL sur la table `utilisateur` (ajout, lecture, modification, suppression) en utilisant PDO.
- **Controller/UserController.php** : Contrôleur qui reçoit les requêtes des vues, appelle les méthodes du modèle et retourne les résultats.
- **View/Backoffice/addUser.php** : Vue qui gère l’ajout d’un utilisateur (reçoit les données, appelle le contrôleur, affiche le résultat).
- **View/Backoffice/readUser.php** : Vue qui affiche la liste des utilisateurs (appelle le contrôleur, affiche les données).
- **View/Backoffice/updateUser.php** : Vue qui gère la modification d’un utilisateur (reçoit les données, appelle le contrôleur, affiche le résultat).
- **View/Backoffice/deleteUser.php** : Vue qui gère la suppression d’un utilisateur (reçoit l’ID, appelle le contrôleur, affiche le résultat).

---

## Explication ligne par ligne

### config.php
```php
class Database {
    private $host = "localhost";
    private $db_name = "gestion_utilisateurs";
    private $username = "root";
    private $password = "";
    public $conn;
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Erreur connexion: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
```
- Définit la classe `Database` pour gérer la connexion à MySQL avec PDO.
- Les paramètres de connexion sont stockés dans des variables privées.
- La méthode `getConnection()` crée une instance PDO et la retourne.
- PDO est configuré pour afficher les erreurs en cas de problème.

---

### Model/User.php
```php
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
```
- Définit le modèle `User` qui utilise PDO pour toutes les opérations SQL.
- Le constructeur initialise la connexion PDO.
- Les méthodes `create`, `readAll`, `update`, `delete` utilisent des requêtes préparées pour la sécurité.

---

### Controller/UserController.php
```php
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
```
- Définit le contrôleur qui fait le lien entre les vues et le modèle.
- Chaque méthode appelle la fonction correspondante du modèle.

---

### View/Backoffice/addUser.php
```php
require_once '../../Controller/UserController.php';
$userController = new \Controller\UserController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'mot_de_passe' => password_hash($_POST['mot_de_passe'] ?? '', PASSWORD_DEFAULT),
        'role' => $_POST['role'] ?? 'user'
    ];
    $result = $userController->createUser($data);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => "Erreur lors de l'ajout de l'utilisateur."]);
    }
    exit;
}
```
- Inclut le contrôleur, crée une instance.
- Si la requête est POST, récupère les données du formulaire, hache le mot de passe, appelle le contrôleur pour ajouter l'utilisateur.
- Affiche le résultat en JSON.

---

### View/Backoffice/readUser.php
```php
require_once '../../Controller/UserController.php';
$userController = new \Controller\UserController();
$users = $userController->readUsers();
if ($users) {
    echo json_encode(['success' => true, 'users' => $users]);
} else {
    echo json_encode(['success' => false, 'message' => 'Aucun utilisateur trouvé.']);
}
```
- Inclut le contrôleur, crée une instance.
- Appelle le contrôleur pour récupérer tous les utilisateurs.
- Affiche la liste en JSON.

---

### View/Backoffice/updateUser.php
```php
require_once '../../Controller/UserController.php';
$userController = new \Controller\UserController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'role' => $_POST['role'] ?? 'user'
    ];
    if ($id) {
        $result = $userController->updateUser($id, $data);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => "Erreur lors de la mise à jour de l'utilisateur."]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant.']);
    }
    exit;
}
```
- Inclut le contrôleur, crée une instance.
- Si la requête est POST, récupère l'ID et les nouvelles données, appelle le contrôleur pour mettre à jour.
- Affiche le résultat en JSON.

---

### View/Backoffice/deleteUser.php
```php
require_once '../../Controller/UserController.php';
$userController = new \Controller\UserController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['id'] ?? null;
    if ($userId) {
        $result = $userController->deleteUser($userId);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => "Erreur lors de la suppression de l'utilisateur."]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant.']);
    }
    exit;
}
```
- Inclut le contrôleur, crée une instance.
- Si la requête est POST, récupère l'ID, appelle le contrôleur pour supprimer.
- Affiche le résultat en JSON.

---

## Utilisation de PDO
- PDO est utilisé dans le modèle `User` pour toutes les requêtes SQL (insertion, lecture, modification, suppression).
- Les requêtes préparées protègent contre les injections SQL et assurent la sécurité des données.
- La connexion PDO est centralisée dans `config.php` et utilisée dans tout le projet via le modèle.

---

Ce projet respecte le modèle MVC, la programmation orientée objet et utilise exclusivement PDO pour la base de données.
