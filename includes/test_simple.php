<?php
// test_simple.php - Prueba MUY simple
require 'includes/conexion.php';

try {
    // Insertar directamente en la tabla (sin funciones)
    $sql = "INSERT INTO email_queue 
            (to_email, to_name, subject, body, alt_body, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'test@example.com',
        'Usuario Test',
        'Prueba Directa',
        '<h1>Prueba</h1>',
        'Prueba texto'
    ]);
    
    $id = $conn->lastInsertId();
    
    echo "✅ Insertado correctamente. ID: $id<br>";
    
    // Contar pendientes
    $stmt = $conn->query("SELECT COUNT(*) as total FROM email_queue");
    $result = $stmt->fetch();
    echo "✅ Total en cola: " . $result['total'];
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>