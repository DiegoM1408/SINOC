<?php
// config.php
class Config {
    const DB_HOST = 'localhost';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';
    
    const BASE_DIR = 'C:/xampp/htdocs/SINOC/modules/Actualizar_Base/'; 
    const UPLOAD_DIR = 'uploads/';
    const LOG_DIR = 'logs/';
    
    const DATABASES = [
        'incidentes' => [
            'name' => 'incidentes_csv',
            'script' => 'importar.js',
            'table' => 'incidentes',
            'description' => 'Base de Incidentes para MinTIC-7K (Service Manager)'
        ],
        'reportes' => [
            'name' => 'incidentes_csv',
            'script' => 'importar2.js',
            'table' => 'reportes',
            'description' => 'Base de Reportes para MinTIC-7K (Portal de Reportes)'
        ],
        'incidentes_odh' => [
            'name' => 'incidentes_csv',
            'script' => 'importarSM-ODH.js',
            'table' => 'incidentes_odh',
            'description' => 'Base de Incidentes ODH (Service Manager ODH)'
        ],
        'reportes_odh' => [
            'name' => 'incidentes_csv',
            'script' => 'importar_reportes_ODH.js',
            'table' => 'reportes_odh',
            'description' => 'Base de Reportes ODH (Portal de Reportes ODH)'
        ],
        'sds_7k' => [
            'name' => 'incidentes_csv',
            'script' => 'importar_SDs_7K.js',
            'table' => 'incidentes_SD_7K',
            'description' => 'Base de SDs para MinTIC-7K'
        ],
        'sds_odh' => [
            'name' => 'incidentes_csv',
            'script' => 'importar_SDs_ODH.js',
            'table' => 'incidentes_SD_ODH',
            'description' => 'Base de SDs para MinTIC-ODH'
        ],
        // NUEVAS BASES DE DATOS SEMILLA
        'semilla_7k' => [
            'name' => 'incidentes_csv',
            'script' => 'importar_semilla_7K-.js',
            'table' => 'inventario_semilla_mintic_7k',
            'description' => 'Base de datos de inventario semilla para MinTIC-7K'
        ],
        'semilla_odh' => [
            'name' => 'incidentes_csv',
            'script' => 'importar_semilla_ODH.js',
            'table' => 'inventario_semilla_mintic_odh',
            'description' => 'Base de datos de inventario semilla para MinTIC-ODH'
        ]
    ];
    
    public static function getUploadPath() {
        return self::BASE_DIR . self::UPLOAD_DIR;
    }
    
    public static function getLogPath() {
        return self::BASE_DIR . self::LOG_DIR;
    }
    
    public static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . 'SINOC/modules/Actualizar_Base/';
    }
}
?>