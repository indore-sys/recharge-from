<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

function readSubmissionData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $jsonInput = file_get_contents('php://input');
        $decoded = json_decode($jsonInput, true);

        if (!is_array($decoded)) {
            throw new Exception('Invalid JSON data received');
        }

        return $decoded;
    }

    return $_POST;
}

function collectUploadedFiles() {
    $files = [];

    foreach ($_FILES as $fieldName => $fileInfo) {
        if (is_array($fileInfo['name'])) {
            $count = count($fileInfo['name']);
            for ($i = 0; $i < $count; $i++) {
                if (!empty($fileInfo['name'][$i])) {
                    $files[$fieldName][$i] = [
                        'name' => $fileInfo['name'][$i],
                        'type' => $fileInfo['type'][$i],
                        'size' => $fileInfo['size'][$i],
                        'tmp_name' => $fileInfo['tmp_name'][$i],
                        'error' => $fileInfo['error'][$i],
                    ];
                }
            }
        } else {
            if (!empty($fileInfo['name'])) {
                $files[$fieldName] = [
                    'name' => $fileInfo['name'],
                    'type' => $fileInfo['type'],
                    'size' => $fileInfo['size'],
                    'tmp_name' => $fileInfo['tmp_name'],
                    'error' => $fileInfo['error'],
                ];
            }
        }
    }

    return $files;
}

function buildStructuredPayload(array $data, array $uploadedFiles) {
    $payload = $data;
    $payload['pageContents'] = [];
    $payload['pageHeadings'] = [];
    $payload['pageSubheadings'] = [];
    $payload['pageImages'] = [];
    $payload['pageAttachments'] = [];

    foreach ($data as $key => $value) {
        if (preg_match('/^(.*)\[\]$/', $key, $matches)) {
            $normalizedKey = $matches[1];
            $payload[$normalizedKey] = is_array($value) ? $value : [$value];
        }

        if (preg_match('/^pageHeading_(.+)$/', $key, $matches)) {
            $payload['pageHeadings'][$matches[1]] = $value;
        } elseif (preg_match('/^pageSubheading_(.+)$/', $key, $matches)) {
            $payload['pageSubheadings'][$matches[1]] = $value;
        } elseif (preg_match('/^content_(.+)$/', $key, $matches) && !preg_match('/^content_.+_file$/', $key)) {
            $payload['pageContents'][$matches[1]] = $value;
        }
    }

    foreach ($uploadedFiles as $fieldName => $fileInfo) {
        $fileName = $fileInfo['name'] ?? '';
        $fileType = $fileInfo['type'] ?? '';
        $tmpName = $fileInfo['tmp_name'] ?? '';
        $fileData = '';

        if ($tmpName && is_readable($tmpName)) {
            $fileData = base64_encode(file_get_contents($tmpName));
        }

        if (preg_match('/^pageImage_(.+)$/', $fieldName, $matches)) {
            $payload['pageImages'][$matches[1]] = [
                'fileName' => $fileName,
                'fileType' => $fileType,
                'data' => $fileData,
            ];
        } elseif (preg_match('/^content_(.+)_file$/', $fieldName, $matches)) {
            $payload['pageAttachments'][$matches[1]] = [
                'fileName' => $fileName,
                'fileType' => $fileType,
                'data' => $fileData,
            ];
        } else {
            $payload[$fieldName] = $fileName;
            $payload[$fieldName . '_type'] = $fileType;
            $payload[$fieldName . '_data'] = $fileData;
        }
    }

    return $payload;
}

try {
    error_log('=== FORM SUBMISSION START ===');
    error_log('Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
    error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

    $data = readSubmissionData();
    $uploadedFiles = collectUploadedFiles();

    error_log('Submitted payload: ' . print_r($data, true));
    error_log('POST dump: ' . print_r($_POST, true));
    error_log('Uploaded files: ' . print_r(array_keys($uploadedFiles), true));

    $client_id = trim($data['client_id'] ?? '');
    if ($client_id === '') {
        $client_id = 'CL-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }

    $name = trim($data['companyName'] ?? $data['contactName'] ?? '');
    if ($name === '') {
        $name = 'Unknown Company';
    }

    $email = trim($data['contactEmail'] ?? '');
    $phone = trim($data['contactPhone'] ?? '');
    $company_name = trim($data['companyName'] ?? $name);

    $payload = buildStructuredPayload($data, $uploadedFiles);

    $conn = getDBConnection();

    $check_sql = "SELECT id FROM clients WHERE client_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $client_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    $form_data_json = json_encode($payload);

    if ($result->num_rows > 0) {
        $sql = "UPDATE clients SET
                name = ?,
                email = ?,
                phone = ?,
                company_name = ?,
                form_data = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE client_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $name, $email, $phone, $company_name, $form_data_json, $client_id);
    } else {
        $sql = "INSERT INTO clients (client_id, name, email, phone, company_name, form_data)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $client_id, $name, $email, $phone, $company_name, $form_data_json);
    }

    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    $stmt->close();
    $check_stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Form submitted successfully',
        'client_id' => $client_id,
        'post_data' => $data,
        'post_data_print_r' => print_r($_POST, true),
        'files' => array_keys($uploadedFiles),
        'structured_keys' => [
            'pageContents' => array_keys($payload['pageContents']),
            'pageHeadings' => array_keys($payload['pageHeadings']),
            'pageSubheadings' => array_keys($payload['pageSubheadings']),
            'pageImages' => array_keys($payload['pageImages']),
            'pageAttachments' => array_keys($payload['pageAttachments']),
        ],
    ]);
} catch (Exception $e) {
    error_log('Form submission error: ' . $e->getMessage());
    error_log('Error trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error_line' => $e->getLine(),
            'error_file' => $e->getFile(),
            'post_data' => $_POST,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
        ]
    ]);
}
