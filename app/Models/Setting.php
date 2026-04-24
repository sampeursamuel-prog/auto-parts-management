<?php
namespace App\Models;

use App\Config\Database;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'id_setting';
    
    /**
     * Récupérer tous les paramètres
     */
    public function getAll()
    {
        return $this->db->fetchAll("SELECT * FROM settings ORDER BY categorie, nom_param");
    }
    
    /**
     * Récupérer un paramètre
     */
    public function get($key, $default = null)
    {
        $result = $this->db->fetchOne("SELECT valeur_param FROM settings WHERE nom_param = ?", [$key]);
        return $result ? $result['valeur_param'] : $default;
    }
    
    /**
     * Définir un paramètre
     */
    public function set($key, $value)
    {
        $exists = $this->db->fetchOne("SELECT 1 FROM settings WHERE nom_param = ?", [$key]);
        if ($exists) {
            return $this->db->query("UPDATE settings SET valeur_param = ? WHERE nom_param = ?", [$value, $key]);
        } else {
            return $this->db->query("INSERT INTO settings (nom_param, valeur_param, type_param, categorie) VALUES (?, ?, 'text', 'general')", [$key, $value]);
        }
    }
    
    /**
     * Initialiser les paramètres par défaut
     */
    public function initDefaults()
    {
        // Vérifier si la table existe
        $tableExists = $this->db->fetchOne("SHOW TABLES LIKE 'settings'");
        if (!$tableExists) {
            return;
        }
        
        $defaults = [
            // Général
            ['nom_param' => 'company_name', 'valeur_param' => 'Total Family Multi-Services', 'type_param' => 'text', 'categorie' => 'general', 'description' => 'Nom de la société'],
            ['nom_param' => 'company_address', 'valeur_param' => 'Rue Principale, Port-au-Prince, Haïti', 'type_param' => 'text', 'categorie' => 'general', 'description' => 'Adresse de la société'],
            ['nom_param' => 'company_phone', 'valeur_param' => '+509 1234 5678', 'type_param' => 'text', 'categorie' => 'general', 'description' => 'Téléphone de contact'],
            ['nom_param' => 'company_email', 'valeur_param' => 'contact@totalfamily.ht', 'type_param' => 'email', 'categorie' => 'general', 'description' => 'Email de contact'],
            ['nom_param' => 'tax_rate', 'valeur_param' => '18', 'type_param' => 'decimal', 'categorie' => 'general', 'description' => 'Taux de TVA (%)'],
            ['nom_param' => 'default_currency', 'valeur_param' => 'HTG', 'type_param' => 'text', 'categorie' => 'general', 'description' => 'Devise par défaut'],
            ['nom_param' => 'maintenance_mode', 'valeur_param' => 'false', 'type_param' => 'boolean', 'categorie' => 'general', 'description' => 'Mode maintenance'],
            
            // Caisse
            ['nom_param' => 'cash_drawer_enabled', 'valeur_param' => 'true', 'type_param' => 'boolean', 'categorie' => 'cash', 'description' => 'Activer le tiroir-caisse'],
            ['nom_param' => 'cash_drawer_port', 'valeur_param' => 'COM1', 'type_param' => 'text', 'categorie' => 'cash', 'description' => 'Port du tiroir-caisse'],
            ['nom_param' => 'require_cashier_login', 'valeur_param' => 'true', 'type_param' => 'boolean', 'categorie' => 'cash', 'description' => 'Obliger la connexion du caissier'],
            
            // Stock
            ['nom_param' => 'low_stock_alert', 'valeur_param' => 'true', 'type_param' => 'boolean', 'categorie' => 'stock', 'description' => 'Activer les alertes stock bas'],
            ['nom_param' => 'low_stock_threshold', 'valeur_param' => '20', 'type_param' => 'integer', 'categorie' => 'stock', 'description' => 'Seuil alerte stock (%)'],
            ['nom_param' => 'default_min_stock', 'valeur_param' => '5', 'type_param' => 'integer', 'categorie' => 'stock', 'description' => 'Stock minimum par défaut'],
            ['nom_param' => 'auto_update_stock', 'valeur_param' => 'true', 'type_param' => 'boolean', 'categorie' => 'stock', 'description' => 'Mise à jour automatique des stocks'],
            
            // Impression
            ['nom_param' => 'printer_type', 'valeur_param' => 'browser', 'type_param' => 'text', 'categorie' => 'impression', 'description' => 'Type d\'imprimante'],
            ['nom_param' => 'printer_ip', 'valeur_param' => '192.168.1.100', 'type_param' => 'text', 'categorie' => 'impression', 'description' => 'Adresse IP de l\'imprimante'],
            ['nom_param' => 'printer_port', 'valeur_param' => '9100', 'type_param' => 'text', 'categorie' => 'impression', 'description' => 'Port de l\'imprimante'],
            ['nom_param' => 'receipt_header', 'valeur_param' => 'MERCI DE VOTRE VISITE', 'type_param' => 'text', 'categorie' => 'impression', 'description' => 'En-tête du ticket'],
            ['nom_param' => 'receipt_footer', 'valeur_param' => 'Cet article ne peut être échangé sans ticket', 'type_param' => 'text', 'categorie' => 'impression', 'description' => 'Pied du ticket'],
            
            // Scanner
            ['nom_param' => 'scanner_type', 'valeur_param' => 'keyboard', 'type_param' => 'text', 'categorie' => 'scanner', 'description' => 'Type de scanner'],
            ['nom_param' => 'scanner_port', 'valeur_param' => 'COM3', 'type_param' => 'text', 'categorie' => 'scanner', 'description' => 'Port du scanner'],
            ['nom_param' => 'barcode_prefix', 'valeur_param' => '', 'type_param' => 'text', 'categorie' => 'scanner', 'description' => 'Prefixe du code-barres'],
            ['nom_param' => 'barcode_suffix', 'valeur_param' => 'enter', 'type_param' => 'text', 'categorie' => 'scanner', 'description' => 'Suffixe du code-barres'],
            
            // Notifications
            ['nom_param' => 'notify_low_stock', 'valeur_param' => 'true', 'type_param' => 'boolean', 'categorie' => 'notifications', 'description' => 'Notification stock bas'],
            ['nom_param' => 'notify_expiring_products', 'valeur_param' => 'false', 'type_param' => 'boolean', 'categorie' => 'notifications', 'description' => 'Notification produits expirés'],
            ['nom_param' => 'notify_daily_sales', 'valeur_param' => 'true', 'type_param' => 'boolean', 'categorie' => 'notifications', 'description' => 'Rapport quotidien des ventes'],
        ];
        
        foreach ($defaults as $default) {
            $exists = $this->db->fetchOne("SELECT 1 FROM settings WHERE nom_param = ?", [$default['nom_param']]);
            if (!$exists) {
                $this->db->query(
                    "INSERT INTO settings (nom_param, valeur_param, type_param, categorie, description) VALUES (?, ?, ?, ?, ?)",
                    [$default['nom_param'], $default['valeur_param'], $default['type_param'], $default['categorie'], $default['description']]
                );
            }
        }
    }
    
    /**
     * Récupérer les paramètres groupés par catégorie
     */
    public function getGrouped()
    {
        $settings = $this->getAll();
        $grouped = [];
        foreach ($settings as $setting) {
            $categorie = $setting['categorie'] ?? 'general';
            if (!isset($grouped[$categorie])) {
                $grouped[$categorie] = [];
            }
            $grouped[$categorie][$setting['nom_param']] = $setting;
        }
        return $grouped;
    }
    
    /**
     * Récupérer les devises
     */
    public function getDevises()
    {
        // Vérifier si la table devises existe
        $tableExists = $this->db->fetchOne("SHOW TABLES LIKE 'devises'");
        if (!$tableExists) {
            return [
                ['code' => 'HTG', 'nom' => 'Gourde', 'taux_htg' => 1],
                ['code' => 'USD', 'nom' => 'Dollar US', 'taux_htg' => 130],
                ['code' => 'EUR', 'nom' => 'Euro', 'taux_htg' => 140]
            ];
        }
        
        $devises = $this->db->fetchAll("SELECT * FROM devises ORDER BY code");
        if (empty($devises)) {
            $devises = [
                ['code' => 'HTG', 'nom' => 'Gourde', 'taux_htg' => 1],
                ['code' => 'USD', 'nom' => 'Dollar US', 'taux_htg' => 130],
                ['code' => 'EUR', 'nom' => 'Euro', 'taux_htg' => 140]
            ];
        }
        return $devises;
    }
    
    /**
     * Mettre à jour le taux de change d'une devise
     */
    public function updateDeviseRate($code, $taux)
    {
        $tableExists = $this->db->fetchOne("SHOW TABLES LIKE 'devises'");
        if (!$tableExists) {
            return false;
        }
        return $this->db->query("UPDATE devises SET taux_htg = ? WHERE code = ?", [$taux, $code]);
    }
    
    /**
     * Mettre à jour tous les taux de change via API
     */
    public function updateExchangeRates()
    {
        // Version simplifiée sans API externe
        // Retourne le taux USD par défaut
        return 130;
    }
}