<?php
session_start();
include("../../../includes/db.php");

if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if (!isset($_GET['table'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Tabla no especificada']);
    exit;
}

$tableId = $_GET['table'];
header('Content-Type: application/json');

try {
    $conn_incidentes = new mysqli($servername, $username, $password, "incidentes_csv");
    
    if ($conn_incidentes->connect_error) {
        throw new Exception("Conexión fallida: " . $conn_incidentes->connect_error);
    }

    $queries = [
        'motivos_pdr' => "SELECT numero_ticket, prioridad, motivo_parada 
                         FROM reportes_odh 
                         WHERE bandera = 'P' 
                         AND prioridad IN ('1', '2')
                         AND (motivo_parada IS NULL OR motivo_parada = '')
                         ORDER BY fecha_apertura DESC
                         LIMIT 100",
        
        'justificaciones_erradas' => "SELECT numero_ticket, motivo_parada, descripcion_apertura_o_parada 
                                    FROM reportes_odh
                                    WHERE bandera = 'P' 
                                    AND prioridad IN ('1', '2')
                                    AND (
                                        (motivo_parada = 'Atribuible Terceros  Sin Contacto con la IE' AND 
                                         descripcion_apertura_o_parada NOT LIKE '%SIN CONTACTO%' AND
                                         descripcion_apertura_o_parada NOT LIKE '%Contacto%' AND
                                         descripcion_apertura_o_parada NOT LIKE '%Sin Contacto%' AND
                                         descripcion_apertura_o_parada NOT LIKE '%CONTACTO%' AND
                                         descripcion_apertura_o_parada NOT LIKE '%sin contacto%' AND
                                         descripcion_apertura_o_parada NOT LIKE '%contacto%' AND
                                         descripcion_apertura_o_parada NOT LIKE '%Sin contacto%')
                                        
                                        OR (motivo_parada IN ('Atribuible Terceros  Falla Energía Eléctrica en IE', 'Atribuible Terceros  Falla Energia Electrica en CD') AND
                                            descripcion_apertura_o_parada NOT LIKE '%ENERGÍA%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%ENERGIA%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%Energía%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%Energia%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%Eléctrico%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%Electrico%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%energía%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%energia%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%eléctrico%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%electrico%')
                                            
                                        OR (motivo_parada = 'Continuidad servicio  Instalaciones no disponibles  Fuera de horario' AND
                                            descripcion_apertura_o_parada NOT LIKE '%FUERA DE HORARIO%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%Fuera de horario%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%Fuera de Horario%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%fuera horario%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%FUERA DE  HORARIO%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%Fuera Horario%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%Fuera horario%' AND
                                            descripcion_apertura_o_parada NOT LIKE '%fuera de horario%')
                                    )
                                    ORDER BY fecha_apertura DESC
                                    LIMIT 100"
    ];

    if (!isset($queries[$tableId])) {
        throw new Exception('Tabla no válida');
    }

    $sql = $queries[$tableId];
    $result = $conn_incidentes->query($sql);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . $conn_incidentes->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $conn_incidentes->close();
    
    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'count' => count($rows)
    ]);
   
    
} catch (Exception $e) {
    if (isset($conn_incidentes)) {
        $conn_incidentes->close();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>