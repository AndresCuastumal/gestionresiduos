<?php
class ReporteContingenciasController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Obtener datos del formulario de contingencias
    public function obtenerDatosContingencias($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT c.*, u.email as nombre_persona_reporta
            FROM contingencias c
            LEFT JOIN usuarios u ON c.persona_reporta = u.id
            WHERE c.generador_id = ? AND c.anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Verificar si existe registro para este año
    public function existeRegistro($generador_id, $anio) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM contingencias 
            WHERE generador_id = ? AND anio = ?
        ");
        $stmt->execute([$generador_id, $anio]);
        return $stmt->fetchColumn() > 0;
    }
    
    // Obtener acciones como array desde JSON
    public function obtenerAccionesJSON($jsonData) {
        if (empty($jsonData)) {
            return [];
        }
        
        $acciones = json_decode($jsonData, true);
        return is_array($acciones) ? $acciones : [];
    }
    
    // Lista de acciones posibles para incendios
    public function obtenerAccionesIncendios() {
        return [
            'instalacion_extintor' => 'Instalación de extintor, detector de humo, aspersor u otro sistema similar',
            'redisenio_area' => 'Rediseño/reubicación del area',
            'verificacion_origen' => 'Verificación de origen del fuego',
            'llamada_bomberos' => 'Llamada a bomberos',
            'otro' => 'Otra acción'
        ];
    }
    
    // Lista de acciones posibles para agua
    public function obtenerAccionesAgua() {
        return [
            'tanque_abastecimiento' => 'Instalación o aumento de capacidad del tanque',
            'sistema_alternativo' => 'Implementación de sistema de suministro alternativo',
            'limpieza_seco' => 'Implementación de sistemas de limpieza en seco',
            'reparacion' => 'Reparación del sistema',
            'otra' => 'Otra acción'
        ];
    }
    
    // Lista de acciones posibles para energía
    public function obtenerAccionesEnergia() {
        return [
            'generador' => 'Uso de generador eléctrico',
            'racionamiento_energia' => 'Racionamiento de energía',
            'reparacion_electrica' => 'Reparación del sistema eléctrico',
            'protocolo_ahorro' => 'Protocolo de ahorro energético',
            'otra' => 'Otra acción'
        ];
    }
    
    // Lista de acciones posibles para derrames
    public function obtenerAccionesDerrames() {
        return [
            'kit_derrame' => 'Utilización de kit de derrame',
            'limpieza_manual' => 'Limpieza manual',
            'apoyo_tercero' => 'Apoyo de terceros especializados',
            'otra' => 'Otra acción'
        ];
    }
    
    // Lista de acciones posibles para recolección
    public function obtenerAccionesRecoleccion() {
        return [
            'gestor_alternativo' => 'Cotratación de gestor alternativo',
            'ampliacion_almacenamiento' => 'Ampliación de capacidad de almacenamiento',
            'negociacion_urgencia' => 'Negociación de urgencia',
            'otra' => 'Otra acción'
        ];
    }
    
    // Lista de acciones posibles para operativas
    public function obtenerAccionesOperativas() {
        return [
            'protocolo_contingencia' => 'Activación protocolo de contingencia',
            'ampliacion_areas' => 'Ampliación de áreas de almacenamiento',
            'gestion_personal' => 'Gestión de personal externo',
            'equipos_alternativos' => 'Uso de equipos alternativos',
            'otro' => 'Otra acción'
        ];
    }
    
    // Tipos de derrames
    public function obtenerTiposDerrames() {
        return [
            'quimico' => 'Químico',
            'petroleo' => 'Petróleo o derivados',
            'biologico' => 'Biológico',
            'otros' => 'Otros'
        ];
    }
}
?>