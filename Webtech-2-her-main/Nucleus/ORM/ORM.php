<?php
namespace Nucleus\ORM;
use AllowDynamicProperties;
use App\Models\Models;
use Nucleus\Container\Container;
use PDO;
use Psr\Http\Message\Request;
use Psr\Http\Message\Uri;

require_once __DIR__ . '/../../vendor/autoload.php';

#[AllowDynamicProperties] class ORM {
    private array $models;
    private PDO $db;

    public function __construct(PDO $pdo, array $models) {
        $this->db = $pdo;
        $this->models = $models;
        $this->container = new Container();
    }

    //Maakt database aan mits deze nog niet bestaat inclusief tabellen.
    public function init(): void
    {
        $this->runRelations(); //Maakt relaties aan in de model classes
        $this->createTables(); //Maakt de tabellen aan
        $this->setupRelationships(); //Past de relaties toe in de database

    }

    //Maakt de tabellen aan
    private function createTables(): void
    {
        foreach ($this->models as $model) {
            $tableName = $model->getName();
            $fields = $model->getRows();
            $hasIdColumn = false;
            foreach ($fields as $name => $type) {
                if ($name === 'id') {
                    $hasIdColumn = true;
                    break;
                }
            }
            if (!$hasIdColumn) {
                $fields = array_merge(['id' => 'INTEGER PRIMARY KEY AUTOINCREMENT'], $fields);
            }
            $columns = [];
            foreach ($fields as $name => $type) {
                $columns[] = "$name $type";
            }
            //Handeld de belongs to waarde van de colom af en maakt een foreign key aan.
            $belongsTo = $model->getBelongsTo();
            foreach ($belongsTo as $relatedModel => $foreignKey) {
                $relatedColumnName = substr($foreignKey, strrpos($foreignKey, '\\') + 1);
                $relatedTableName = (new $relatedModel())->getName();
                $columns[] = "FOREIGN KEY ($relatedColumnName) REFERENCES $relatedTableName(id)";
            }
            $sql = "CREATE TABLE IF NOT EXISTS $tableName (" . implode(',', $columns) . ")";
            $this->db->exec($sql);
        }
    }

    //Maakt de koppeltabellen aan en past relaties hier in toe
    private function setupRelationships(): void
    {
        foreach ($this->models as $model) {
            $this->createPivotTables($model);
        }
    }
    //Maakt relaties aan in de model classes
    private function runRelations(): void
    {
        foreach ($this->models as $model) {
            $model->relationships();
        }
    }
    //Aanmaken koppeltabel tussen deck en card op deck.id en card.id
    private function createPivotTables(Models $model): void
    {
        $belongsToMany = $model->getBelongsToMany();
        foreach ($belongsToMany as $relatedModel => $relation) {
            $junctionTable = $relation['junction_table'];
            if (!$this->tableExists($junctionTable)) {
                $relatedModelInstance = $this->getInjectedModelInstance($relatedModel);
                if ($relatedModelInstance !== null) {
                    $relatedTableName = $relatedModelInstance->getName();
                    $currentTableName = $model->getName();
                    $sql = "CREATE TABLE IF NOT EXISTS $junctionTable (
                    {$relation['foreign_key']} INTEGER,
                    {$relation['related_key']} INTEGER,
                    PRIMARY KEY ({$relation['foreign_key']}, {$relation['related_key']}),
                    FOREIGN KEY ({$relation['foreign_key']}) REFERENCES $relatedTableName(id) ON DELETE CASCADE,
                    FOREIGN KEY ({$relation['related_key']}) REFERENCES $currentTableName(id) ON DELETE CASCADE
                )";
                    $this->db->exec($sql);
                } else {
                    echo "Related model instance for $relatedModel not found.";
                }
            }
        }
    }

    //Getter voor DI geinjecteerde model op basis van naam
    private function getInjectedModelInstance(string $modelName) {
        foreach ($this->models as $model) {
            var_dump(get_class($model));
            var_dump($modelName);
            if (get_class($model) === 'App\Models\\' . $modelName)
            {
                echo "<br> FOUND";
                var_dump($model);
                return $model;
            }
        }
        return null;
    }

    //Controlleerd of tabel bestaat
    private function tableExists($tableName): bool
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=:tableName";
        $statement = $this->db->prepare($sql);
        $statement->execute([':tableName' => $tableName]);
        return $statement->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function addCard(string $name, int $attack, int $defense, string $series, string $rarity, float $market_price, string $image): void
    {
        $sqlCheck = "SELECT COUNT(*) AS count FROM card WHERE name = :name";
        $statementCheck = $this->db->prepare($sqlCheck);
        $statementCheck->bindParam(':name', $name);
        $statementCheck->execute();
        $result = $statementCheck->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            echo("Card with name '$name' already exists.");
            return;
        }
        $sql = "INSERT INTO card (name, attack, defense, series, rarity, market_price, image) 
        VALUES (:name, :attack, :defense, :series, :rarity, :market_price, :image)";

        $statement = $this->db->prepare($sql);
        $statement->bindParam(':name', $name);
        $statement->bindParam(':attack', $attack);
        $statement->bindParam(':defense', $defense);
        $statement->bindParam(':series', $series);
        $statement->bindParam(':rarity', $rarity);
        $statement->bindParam(':market_price', $market_price);
        $statement->bindParam(':image', $image);
        $statement->execute();
    }


    public function addUser(string $name, string $email, string $password, string $role)
    {
        if (empty($this->getUser($email))) {
            $sql = "INSERT INTO user (name, email, password, role) VALUES (:name, :email, :password, :role)";
            $statement = $this->db->prepare($sql);
            $statement->bindParam(':name', $name);
            $statement->bindParam(':email', $email);
            $statement->bindParam(':password', $password);
            $statement->bindParam(':role', $role);
            $statement->execute();
        }

        $this->container->set('uri', new Uri('register'));
        $uri = $this->container->get('uri');
        return new Request('GET', $uri, 'Email is already taken', []);

    }

    public function getUser($email): array
    {
        $sql = "SELECT * FROM user WHERE email = :email";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':email', $email);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) : array
    {
        $sql = "SELECT * FROM user WHERE id = :id";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $id);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($table, $id): void
    {
        $sql = "DELETE FROM $table WHERE id = $id";
        $statement = $this->db->prepare($sql);
        $statement->execute();

    }

    public function getDecks($id) : array
    {
        $sql = "SELECT * FROM deck WHERE user_id = :id";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $id);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeck($name, $id) : array
    {
        $sql = "SELECT * FROM deck WHERE user_id = :id AND name = :name";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $id);
        $statement->bindParam(':name', $name);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addDeck($name, $user_id): void
    {
        $sqlCheck = "SELECT COUNT(*) AS count FROM deck WHERE name = :name AND user_id = :user_id";
        $statementCheck = $this->db->prepare($sqlCheck);
        $statementCheck->bindParam(':name', $name);
        $statementCheck->bindParam(':user_id', $user_id);
        $statementCheck->execute();
        $result = $statementCheck->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            echo("Deck with name '$name' already exists for this user.");
            return;
        }
        $sql = "INSERT INTO deck (name, user_id) 
        VALUES (:name, :user_id)";

        $statement = $this->db->prepare($sql);
        $statement->bindParam(':name', $name);
        $statement->bindParam(':user_id', $user_id);
        $statement->execute();
    }

    public function clear($table): void
    {
        $sql = "DELETE FROM $table";
        $statement = $this->db->prepare($sql);
        $statement->execute();
    }

    public function updateRole($id, $role): void
    {
        $sql = "UPDATE user SET role = :role WHERE id = :id ";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $id);
        $statement->bindParam(':role', $role);
        $statement->execute();
    }

    public function updateCard($id, $rarity, $attack, $defense, $series, $market_price): void
    {
        $sql = "UPDATE card SET rarity = :rarity, attack = :attack, defense = :defense, series = :series, market_price = :market_price WHERE id = :id ";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $id);
        $statement->bindParam(':rarity', $rarity);
        $statement->bindParam(':attack', $attack);
        $statement->bindParam(':defense', $defense);
        $statement->bindParam(':series', $series);
        $statement->bindParam(':market_price', $market_price);
        $statement->execute();
    }

    public function select($table, $id) : array
    {
        $sql = "SELECT * FROM $table WHERE id = $id";
        $statement = $this->db->prepare($sql);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count($table) : array
    {
        $sql = "SELECT COUNT(id) AS NumberOfRecords FROM $table;";
        $statement = $this->db->prepare($sql);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($table) : array
    {
        $sql = "SELECT * FROM $table";
        $statement = $this->db->prepare($sql);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeckCards($deck_id) : array
    {
        $sql = "SELECT * FROM deck_card WHERE deck_id = :deck_id";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':deck_id', $deck_id);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCard($card_id) : array
    {
        $sql = "SELECT * FROM card WHERE id = :id";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':id', $card_id);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCardIdFromName($name) : array
    {
        $sql = "SELECT id FROM card WHERE name = :name";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':name', $name);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRow($table, $row) : array
    {
        $sql = "SELECT $row FROM $table";
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':table', $table);
        $statement->bindParam(':row', $row);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addCardToDeck($deck_id, $card_id): void
    {
        $sqlCheck = "SELECT COUNT(*) AS count FROM deck_card WHERE deck_id = :deck_id AND card_id = :card_id";
        $statementCheck = $this->db->prepare($sqlCheck);
        $statementCheck->bindParam(':deck_id', $deck_id);
        $statementCheck->bindParam(':card_id', $card_id);
        $statementCheck->execute();
        $result = $statementCheck->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            echo("Deck already contains this card.");
            return;
        }
        $sql = "INSERT INTO deck_card (deck_id, card_id) 
        VALUES (:deck_id, :card_id)";

        $statement = $this->db->prepare($sql);
        $statement->bindParam(':deck_id', $deck_id);
        $statement->bindParam(':card_id', $card_id);
        $statement->execute();
    }

}