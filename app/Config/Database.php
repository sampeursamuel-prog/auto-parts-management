<?php
namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;
    
    private function __construct()
    {
        $host = 'localhost';
        $dbname = 'autoparts_db';
        $username = 'root';
        $password = '';
        
        try {
            $this->connection = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $username,
                $password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur de connexion: " . $e->getMessage());
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    public function query($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }
    
    // ============================================
    // MÉTHODES DE TRANSACTION
    // ============================================
    
    /**
     * Démarre une transaction
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Valide (commit) une transaction
     * @return bool
     */
    public function commit()
    {
        return $this->connection->commit();
    }
    
    /**
     * Annule (rollback) une transaction
     * @return bool
     */
    public function rollBack()
    {
        return $this->connection->rollBack();
    }
    
    /**
     * Vérifie si une transaction est active
     * @return bool
     */
    public function inTransaction()
    {
        return $this->connection->inTransaction();
    }
}