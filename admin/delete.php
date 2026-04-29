<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get database connection
$conn = getDBConnection();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? '';
    
    if (empty($client_id)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Client ID is required']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete from clients table
        $stmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
        $stmt->bind_param('s', $client_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Client not found');
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Client deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    $conn->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
