<?php
namespace App\Models;

use App\Config\Database;

class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function all()
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table} WHERE est_actif = 1");
    }
    
    public function find($id)
    {
        return $this->db->fetchOne("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
    }
    
    public function create($data)
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->query($sql, array_values($data));
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data)
    {
        $set = [];
        $values = [];
        foreach ($data as $field => $value) {
            $set[] = "{$field} = ?";
            $values[] = $value;
        }
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE {$this->primaryKey} = ?";
        return $this->db->query($sql, $values);
    }
    
    public function delete($id)
    {
        return $this->db->query("UPDATE {$this->table} SET est_actif = 0 WHERE {$this->primaryKey} = ?", [$id]);
    }
}