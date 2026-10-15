<?php
// includes/email_queue.php - Manejo de cola de correos
require_once 'conexion.php';
require_once 'enviar_correo.php';

/**
 * Agrega un correo a la cola para envío diferido
 */
function agregarCorreoALaCola($to_email, $to_name, $subject, $body, $alt_body = null) {
    global $conn; // Usamos la conexión global de conexion.php
    
    try {
        $sql = "INSERT INTO email_queue 
                (to_email, to_name, subject, body, alt_body, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $to_email,
            $to_name,
            $subject,
            $body,
            $alt_body
        ]);
        
        return $conn->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Error al agregar correo a la cola: " . $e->getMessage());
        return false;
    }
}

/**
 * Función de prueba simple
 */
function testAgregarCola() {
    $id = agregarCorreoALaCola(
        'test@example.com',
        'Test User',
        'Correo de Prueba',
        '<h1>Test</h1><p>Prueba de sistema de cola</p>',
        'Prueba de sistema de cola'
    );
    
    return $id ? "✅ ID: $id" : "❌ Error";
}