<?php
/**
 * Configuración del Sidebar - Sistema NOC Claro
 * Define la estructura completa del menú con todos los módulos
 */

// Obtener la página actual
$current_page = basename($_SERVER['PHP_SELF']);

// Estructura completa del menú del sidebar
$sidebar_menu = [
    [
        'type' => 'single',
        'icon' => 'bx-home',
        'label' => 'Inicio',
        'url' => BASE_URL . '/index.php',
        'page' => 'index.php'
    ],  
    [
        'type' => 'dropdown',
        'icon' => 'bx-network-chart',
        'label' => 'MinTIC 7K',
        'id' => 'mintic7k',
        'items' => [
            [
                'icon' => 'bx-search-alt',
                'label' => 'Auditorías',
                'url' => BASE_URL . '/modules/MinTIC_7k/auditoria/auditoria_7k.php',
                'page' => 'auditoria_7k.php'
            ],
            [
                'icon' => 'bx-calendar',
                'label' => 'PDRs y Fechas',
                'url' => BASE_URL . '/modules/MinTIC_7k/PDRs_Fechas/pdr_fechas_7k.php',
                'page' => 'pdr_fechas_7k.php'
            ],
            [
                'icon' => 'bx-transfer-alt',
                'label' => 'Comparación Bases',
                'url' => BASE_URL . '/modules/MinTIC_7k/comparacion_bases/comparacion_bases_index.php',
                'page' => 'comparacion_bases_index.php'
            ],
            [
                'icon' => 'bx-file',
                'label' => 'SDs',
                'url' => BASE_URL . '/modules/MinTIC_7k/SDs/SDs_7K.php',
                'page' => 'SDs_7K.php'
            ]
        ]
    ],
    [
        'type' => 'dropdown',
        'icon' => 'bx-chip',
        'label' => 'MinTIC ODH 5G',
        'id' => 'minticodh',
        'items' => [
            [
                'icon' => 'bx-search-alt',
                'label' => 'Auditorías ODH',
                'url' => BASE_URL . '/modules/MinTIC_ODH/auditoria_ODH/auditoria_ODH.php',
                'page' => 'auditoria_ODH.php'
            ],
            [
                'icon' => 'bx-calendar',
                'label' => 'PDRs y Fechas ODH',
                'url' => BASE_URL . '/modules/MinTIC_ODH/PDRs_Fechas_ODH/PDRs_Fechas_index.php',
                'page' => 'PDRs_Fechas_index.php'
            ],
            [
                'icon' => 'bx-transfer-alt',
                'label' => 'Comparación Bases ODH',
                'url' => BASE_URL . '/modules/MinTIC_ODH/comparacion_bases_ODH/comparacion_bases_index_ODH.php',
                'page' => 'comparacion_bases_index_ODH.php'
            ],
            [
                'icon' => 'bx-file',
                'label' => 'SDs ODH',
                'url' => BASE_URL . '/modules/MinTIC_ODH/SDs/SDs_ODH.php',
                'page' => 'SDs_ODH.php'
            ]
        ]
    ],
    [
        'type' => 'single',
        'icon' => 'bx-cog',
        'label' => 'Configuración',
        'url' => BASE_URL . '/modules/configuracion/configuracion.php',
        'page' => 'configuracion.php'
    ]
];

if (isset($_SESSION['role']) && $_SESSION['role'] == 1 || $_SESSION['role'] == 2) {
    $sidebar_menu[] = [
        'type' => 'single',
        'icon' => 'bx-refresh',
        'label' => 'Actualizar Bases',
        'url' => BASE_URL . '/modules/Actualizar_Base/actualizar_bases.php',
        'page' => 'actualizar_bases.php'
    ];
}

// Agregar Gestión de Usuarios solo para administradores
if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
    $sidebar_menu[] = [
        'type' => 'single',
        'icon' => 'bx-user-plus',
        'label' => 'Gestión de Usuarios',
        'url' => BASE_URL . '/modules/gestion_usuario/gestion_usuarios.php',
        'page' => 'gestion_usuarios.php'
    ];
}

/**
 * Función para verificar si un menú está activo
 */
function isMenuActive($menu_item, $current_page) {
    if ($menu_item['type'] === 'single') {
        return isset($menu_item['page']) && $menu_item['page'] === $current_page;
    } elseif ($menu_item['type'] === 'dropdown' && isset($menu_item['items'])) {
        foreach ($menu_item['items'] as $item) {
            if (isset($item['page']) && $item['page'] === $current_page) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Función para verificar si un dropdown debe estar abierto
 */
function isDropdownActive($dropdown_items, $current_page) {
    foreach ($dropdown_items as $item) {
        if (isset($item['page']) && $item['page'] === $current_page) {
            return true;
        }
    }
    return false;
}
?>