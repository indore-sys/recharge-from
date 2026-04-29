<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'config.php';

$client_id = trim($_GET['client_id'] ?? '');
$type = trim($_GET['type'] ?? '');
$page = trim($_GET['page'] ?? '');
$download = isset($_GET['download']) && $_GET['download'] === '1';

if ($client_id === '' || $type === '') {
    http_response_code(400);
    exit('Missing required parameters');
}

$conn = getDBConnection();
$stmt = $conn->prepare('SELECT form_data FROM clients WHERE client_id = ? LIMIT 1');
$stmt->bind_param('s', $client_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    http_response_code(404);
    exit('Client not found');
}

$form_data = json_decode($row['form_data'] ?? '{}', true);
if (!is_array($form_data)) {
    $form_data = [];
}

function stream_file(string $base64Data, string $fileName, string $mimeType, bool $download): void
{
    $binary = base64_decode($base64Data, true);
    if ($binary === false) {
        http_response_code(500);
        exit('Invalid file data');
    }

    header('Content-Type: ' . ($mimeType ?: 'application/octet-stream'));
    $disposition = $download ? 'attachment' : 'inline';
    header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($fileName ?: 'download') . '"');
    header('Content-Length: ' . strlen($binary));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo $binary;
    exit;
}

switch ($type) {
    case 'logo':
        $logo = $form_data['logoFile_data'] ?? null;
        if (is_array($logo) && !empty($logo['data'])) {
            stream_file(
                (string) $logo['data'],
                (string) ($logo['fileName'] ?? 'logo'),
                (string) ($logo['fileType'] ?? 'application/octet-stream'),
                $download
            );
        } elseif (is_string($logo) && !empty($logo)) {
            // Handle old format where data is stored as plain base64 string
            stream_file(
                (string) $logo,
                (string) ($form_data['logoFile'] ?? 'logo.jpg'),
                (string) ($form_data['logoFile_type'] ?? 'image/jpeg'),
                $download
            );
        }
        break;

    case 'page-image':
        if ($page !== '' && !empty($form_data['pageImages'][$page])) {
            $image = $form_data['pageImages'][$page];
            if (is_array($image) && !empty($image['data'])) {
                stream_file(
                    (string) $image['data'],
                    (string) ($image['fileName'] ?? ($page . '.jpg')),
                    (string) ($image['fileType'] ?? 'application/octet-stream'),
                    $download
                );
            } elseif (is_string($image) && !empty($image)) {
                // Handle old format where data is stored as plain base64 string
                stream_file(
                    (string) $image,
                    (string) ($page . '.jpg'),
                    'image/jpeg',
                    $download
                );
            }
        }
        break;

    case 'page-attachment':
        if ($page !== '' && !empty($form_data['pageAttachments'][$page])) {
            $attachment = $form_data['pageAttachments'][$page];
            if (is_array($attachment) && !empty($attachment['data'])) {
                stream_file(
                    (string) $attachment['data'],
                    (string) ($attachment['fileName'] ?? ($page . '-attachment')),
                    (string) ($attachment['fileType'] ?? 'application/octet-stream'),
                    $download
                );
            } elseif (is_string($attachment) && !empty($attachment)) {
                // Handle old format where data is stored as plain base64 string
                stream_file(
                    (string) $attachment,
                    (string) ($page . '-attachment'),
                    'application/octet-stream',
                    $download
                );
            }
        }
        break;

    case 'service-details':
        $serviceData = $form_data['serviceDetailsFile_data'] ?? null;
        if (is_array($serviceData) && !empty($serviceData['data'])) {
            stream_file(
                (string) $serviceData['data'],
                (string) ($serviceData['fileName'] ?? 'service-details.pdf'),
                (string) ($serviceData['fileType'] ?? 'application/pdf'),
                $download
            );
        } elseif (is_string($serviceData) && !empty($serviceData)) {
            // Handle old format where data is stored as plain base64 string
            stream_file(
                (string) $serviceData,
                (string) ($form_data['serviceDetailsFile'] ?? 'service-details.pdf'),
                (string) ($form_data['serviceDetailsFile_type'] ?? 'application/pdf'),
                $download
            );
        }
        break;
}

http_response_code(404);
exit('Asset not found');
