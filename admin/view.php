<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get client ID from URL
$client_id = $_GET['id'] ?? '';
if (empty($client_id)) {
    header('Location: index.php');
    exit();
}

// Get database connection
$conn = getDBConnection();

// Get client data
$sql = "SELECT * FROM clients WHERE client_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$client = $result->fetch_assoc();
$form_data = json_decode($client['form_data'], true);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? 'New';
    
    $update_sql = "UPDATE clients SET status = ? WHERE client_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $new_status, $client_id);
    $update_stmt->execute();
    
    $client['status'] = $new_status;
}

$stmt->close();
$conn->close();

// Helper function to display field value
function displayValue($value) {
    if (empty($value) || $value === 'Not Provided') {
        return '<span style="color: #999; font-size: 14px;">Not Provided</span>';
    }
    return '<span style="font-size: 14px;">' . htmlspecialchars($value) . '</span>';
}

// Helper function to display array values
function displayArray($values) {
    if (empty($values)) {
        return '<span style="color: #999; font-size: 14px;">Not Provided</span>';
    }
    if (!is_array($values)) {
        $values = [$values];
    }
    return '<span style="font-size: 14px;">' . htmlspecialchars(implode(', ', $values)) . '</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - <?php echo htmlspecialchars($client['client_id']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 12px;
            line-height: 1.65;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }

        .btn-pdf {
            background: #28a745;
            color: white;
        }

        .btn-pdf:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        /* Custom Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-icon {
            font-size: 14px;
        }

        .modal-message {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-modal-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-modal-cancel:hover {
            background: #5a6268;
        }

        .btn-modal-confirm {
            background: #dc3545;
            color: white;
        }

        .btn-modal-confirm:hover {
            background: #c82333;
        }

        /* Success Modal Styles */
        .modal-success .modal-title {
            color: #28a745;
        }

        .modal-success .btn-modal-confirm {
            background: #28a745;
        }

        .modal-success .btn-modal-confirm:hover {
            background: #218838;
        }

        .container {
            max-width: 1360px;
            margin: 34px auto;
            padding: 0 24px;
        }

        .client-info {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 34px;
        }

        .client-info h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .client-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 22px;
        }

        .detail-item {
            padding: 18px 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .detail-value {
            color: #333;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .section {
            background: white;
            margin-bottom: 34px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px 30px;
            font-size: 20px;
            font-weight: 600;
        }

        .section-content {
            padding: 32px;
        }

        .field-group {
            margin-bottom: 30px;
        }

        .field-group:last-child {
            margin-bottom: 0;
        }

        .field-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .field-value {
            color: #333;
            line-height: 1.65;
            font-size: 14px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }

        .checkbox-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .checkbox-item {
            background: #f0f0f0;
            padding: 7px 14px;
            border-radius: 18px;
            font-size: 14px;
        }

        .file-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .file-status.uploaded {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .file-status.not-provided {
            background: #fff3e0;
            color: #f57c00;
        }

        .page-structure {
            background: #f8f9fa;
            padding: 22px;
            border-radius: 12px;
            margin-top: 10px;
        }

        .page-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .page-item:last-child {
            border-bottom: none;
        }

        .page-name {
            flex: 1;
            font-weight: 500;
            font-size: 14px;
        }

        .page-type {
            background: #667eea;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 14px;
            margin-left: 10px;
        }

        .subsection {
            margin-top: 32px;
            padding: 24px;
            background: #fbfcff;
            border: 1px solid #e8eef7;
            border-radius: 14px;
        }

        .subsection h4 {
            font-size: 18px;
        }

        .content-card {
            margin-bottom: 28px;
            padding: 24px;
            border: 1px solid #e9ecef;
            border-radius: 14px;
            background: #f8f9fa;
        }

        .content-card__title {
            margin: 0 0 14px 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 700;
        }

        .content-card__section {
            margin-bottom: 20px;
            padding: 18px 18px 16px;
            border-radius: 12px;
        }

        .content-card__section--copy {
            background: #fffdf5;
            border-left: 4px solid #f39c12;
        }

        .content-card__section--settings {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
        }

        .content-card__label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 700;
            font-size: 14px;
        }

        .content-card__value {
            color: #555;
            margin-top: 4px;
            line-height: 1.8;
            font-size: 14px;
            word-break: break-word;
        }

        .content-chip {
            border-radius: 18px;
            padding: 7px 14px;
            font-size: 14px;
        }

        .content-badge-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 12px;
        }

        @media (max-width: 768px) {
            .client-details,
            .field-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .container {
                padding: 0 14px;
            }

            .section-content,
            .client-info {
                padding: 22px;
            }
        }

        @media print {
            .header-actions,
            .btn-pdf,
            .status-form {
                display: none;
            }

            .section {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .client-info,
            .field-group,
            .field-grid {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            h2, h3 {
                break-after: avoid;
                page-break-after: avoid;
            }
        }

        /* PDF-specific styles */
        #pdfContent {
            page-break-inside: auto;
        }

        .section {
            page-break-inside: avoid;
            margin-bottom: 10px;
        }

        .field-group {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>Client Details: <?php echo htmlspecialchars($client['client_id']); ?></h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                <button onclick="generatePDF(event)" class="btn btn-pdf">📄 Download PDF</button>
                <button onclick="deleteClient()" class="btn btn-delete">🗑️ Delete</button>
            </div>
        </div>
    </header>

    <div class="container" id="pdfContent">
        <!-- Logo for PDF -->
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="../Rechh looo.png" alt="Logo" style="max-width: 180px; height: auto;">
        </div>

        <!-- Client Information -->
        <div class="client-info">
            <h2>Client Information</h2>
            <div class="client-details">
                <div class="detail-item">
                    <div class="detail-label">Client ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($client['client_id']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo displayValue($client['name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo displayValue($client['email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?php echo displayValue($client['phone']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Company</div>
                    <div class="detail-value"><?php echo displayValue($client['company_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Submission Date</div>
                    <div class="detail-value"><?php echo date('M j, Y H:i', strtotime($client['created_at'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <form method="POST" class="status-form">
                            <select name="status" class="status-select">
                                <option value="New" <?php echo $client['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                <option value="In Progress" <?php echo $client['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo $client['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-primary" style="padding: 10px 18px; font-size: 1rem;">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Project Basics -->
        <div class="section">
            <div class="section-header">1. Project Basics</div>
            <div class="section-content">
                <div class="field-grid">
                    <div class="field-group">
                        <div class="field-label">Project Type</div>
                        <div class="field-value"><?php echo displayValue($form_data['projectType'] ?? ''); ?></div>
                    </div>
                    <div class="field-group">
                        <div class="field-label">Business Category</div>
                        <div class="field-value">
                            <?php 
                            $businessCategory = $form_data['businessCategory'] ?? '';
                            $categoryLabels = [
                                'retail' => 'Retail & E-commerce',
                                'healthcare' => 'Healthcare & Medical',
                                'education' => 'Education & Training',
                                'technology' => 'Technology & IT Services',
                                'restaurant' => 'Restaurant & Food Services',
                                'realestate' => 'Real Estate & Construction',
                                'consulting' => 'Consulting & Professional Services',
                                'manufacturing' => 'Manufacturing & Industrial',
                                'other' => 'Other'
                            ];
                            echo displayValue($categoryLabels[$businessCategory] ?? $businessCategory);
                            ?>
                        </div>
                    </div>
                    <div class="field-group">
                        <div class="field-label">Target Launch Date</div>
                        <div class="field-value"><?php echo displayValue($form_data['launchDate'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label">Project Description</div>
                    <div class="field-value"><?php echo displayValue($form_data['projectDescription'] ?? ''); ?></div>
                </div>
                <?php if (!empty($form_data['otherProjectTypeInput'])): ?>
                    <div class="field-group">
                        <div class="field-label">Other Project Type</div>
                        <div class="field-value"><?php echo displayValue($form_data['otherProjectTypeInput']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($form_data['otherBusinessCategoryInput'])): ?>
                    <div class="field-group">
                        <div class="field-label">Other Business Category</div>
                        <div class="field-value"><?php echo displayValue($form_data['otherBusinessCategoryInput']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 2: Technical Setup -->
        <div class="section">
            <div class="section-header">2. Technical Setup</div>
            <div class="section-content">
                <div class="field-grid">
                    <div class="field-group">
                        <div class="field-label">Preferred Platform</div>
                        <div class="field-value"><?php echo displayValue($form_data['platform'] ?? ''); ?></div>
                    </div>
                </div>
                
                <?php if (!empty($form_data['preferredCms'])): ?>
                    <div class="field-group">
                        <div class="field-label">Preferred CMS</div>
                        <div class="field-value"><?php echo displayValue($form_data['preferredCms']); ?></div>
                    </div>
                <?php endif; ?>

                <div class="field-group">
                    <div class="field-label">Domain Information</div>
                    <div class="field-value">
                        <strong>Has Domain:</strong> <?php echo displayValue($form_data['hasDomain'] ?? ''); ?><br>
                        <?php if (!empty($form_data['domainName'])): ?>
                            <strong>Domain Name:</strong> <?php echo displayValue($form_data['domainName']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['domainProvider'])): ?>
                            <strong>Provider:</strong> <?php echo displayValue($form_data['domainProvider']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['preferredDomain'])): ?>
                            <strong>Preferred Domain:</strong> <?php echo displayValue($form_data['preferredDomain']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-label">Hosting Information</div>
                    <div class="field-value">
                        <strong>Has Hosting:</strong> <?php echo displayValue($form_data['hasHosting'] ?? ''); ?><br>
                        <?php if (!empty($form_data['hostingProvider'])): ?>
                            <strong>Hosting Provider:</strong> <?php echo displayValue($form_data['hostingProvider']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['hostingPlan'])): ?>
                            <strong>Hosting Plan:</strong> <?php echo displayValue($form_data['hostingPlan']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['hostingUsername'])): ?>
                            <strong>Hosting Username:</strong> <?php echo displayValue($form_data['hostingUsername']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['hostingPassword'])): ?>
                            <strong>Hosting Password:</strong> <span style="background: #fff3cd; padding: 2px 6px; border-radius: 3px; font-family: monospace;"><?php echo displayValue($form_data['hostingPassword']); ?></span><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['cpanelUrl'])): ?>
                            <strong>cPanel URL:</strong> <?php echo displayValue($form_data['cpanelUrl']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($form_data['hasDomain']) && $form_data['hasDomain'] === 'yes'): ?>
                    <div class="field-group">
                        <div class="field-label">Domain Account Details</div>
                        <div class="field-value">
                            <?php if (!empty($form_data['domainUsername'])): ?>
                                <strong>Domain Username:</strong> <?php echo displayValue($form_data['domainUsername']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($form_data['domainPassword'])): ?>
                                <strong>Domain Password:</strong> <span style="background: #fff3cd; padding: 2px 6px; border-radius: 3px; font-family: monospace;"><?php echo displayValue($form_data['domainPassword']); ?></span><br>
                            <?php endif; ?>
                            <?php if (!empty($form_data['domainRegistrar'])): ?>
                                <strong>Registrar Panel:</strong> <?php echo displayValue($form_data['domainRegistrar']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Security & SSL Section -->
                <div class="field-group">
                    <div class="field-label">Security & SSL</div>
                    <div class="field-value">
                        <?php if (!empty($form_data['hasSSL'])): ?>
                            <strong>SSL Available:</strong> <?php echo displayValue($form_data['hasSSL']); ?><br>
                            <?php if ($form_data['hasSSL'] === 'yes'): ?>
                                <?php if (!empty($form_data['sslProvider'])): ?>
                                    <strong>Provider:</strong> <?php echo displayValue($form_data['sslProvider']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($form_data['sslExpiryDate'])): ?>
                                    <strong>Expiry Date:</strong> <?php echo displayValue($form_data['sslExpiryDate']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($form_data['sslType'])): ?>
                                    <strong>SSL Type:</strong> <?php echo displayValue($form_data['sslType']); ?><br>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (!empty($form_data['sslPurchase'])): ?>
                                    <strong>Purchase Required:</strong> <?php echo displayValue($form_data['sslPurchase']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($form_data['sslPreferredProvider'])): ?>
                                    <strong>Preferred Provider:</strong> <?php echo displayValue($form_data['sslPreferredProvider']); ?><br>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;">Not Provided</span>
                        <?php endif; ?>
                        <?php if (!empty($form_data['securityRequirements'])): ?>
                            <strong>Security Requirements:</strong> <?php echo displayValue($form_data['securityRequirements']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-label">Theme Information</div>
                    <div class="field-value">
                        <strong>Has Theme:</strong> <?php echo displayValue($form_data['hasTheme'] ?? ''); ?><br>
                        <?php if (!empty($form_data['themeName'])): ?>
                            <strong>Theme Name:</strong> <?php echo displayValue($form_data['themeName']); ?><br>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Content & Legal -->
        <div class="section">
            <div class="section-header">3. Content & Legal</div>
            <div class="section-content">
                
                <!-- Branding Subsection -->
                <div class="subsection">
                    <h4 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; margin-bottom: 15px;">🎨 BRANDING INFORMATION</h4>
                    <div class="field-grid">
                        <div class="field-group">
                            <div class="field-label">Company Name</div>
                            <div class="field-value"><?php echo displayValue($form_data['companyName'] ?? 'Not Provided'); ?></div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Tagline/Slogan</div>
                            <div class="field-value"><?php echo displayValue($form_data['tagline'] ?? 'Not Provided'); ?></div>
                        </div>
                    </div>

                    <div class="field-grid">
                        <div class="field-group">
                            <div class="field-label">Logo Status</div>
                            <div class="field-value"><?php echo displayValue($form_data['hasLogo'] ?? 'Not Provided'); ?></div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Logo File Link</div>
                            <div class="field-value">
                                <?php 
                                if (!empty($form_data['logoLink'])) {
                                    echo '<a href="' . htmlspecialchars($form_data['logoLink']) . '" target="_blank" style="color: #3498db; text-decoration: none;">🔗 View Logo File</a>';
                                } else {
                                    echo 'Not Provided';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                   <?php if (!empty($form_data['logoFile_data'])): ?>
                    <div class="field-group">
                        <div class="field-label">Uploaded Logo File</div>
                        <div class="field-value">
                            <?php 
                            $logoData = $form_data['logoFile_data'];
                            if ((is_array($logoData) && !empty($logoData['data'])) || (is_string($logoData) && !empty($logoData))): 
                            ?>
                                <!-- IMAGE -->
                                <img src="../download_asset.php?client_id=<?php echo urlencode($client['client_id']); ?>&type=logo" 
                                    alt="Logo" 
                                    style="max-width: 200px; height: auto; border-radius: 8px; border: 1px solid #ddd;">

                                <!-- 🔥 ADD THIS ONLY -->
                                <br><br>
                                <a href="../download_asset.php?client_id=<?php echo urlencode($client['client_id']); ?>&type=logo" 
                                download>
                                ⬇ Download Logo
                                </a>

                            <?php else: ?>
                                <span style="color: #7f8c8d; font-style: italic;">Logo file data not available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                    <?php if (!empty($form_data['logoSize'])): ?>
                        <div class="field-group">
                            <div class="field-label">Logo Size (Dimensions)</div>
                            <div class="field-value"><?php echo displayValue($form_data['logoSize']); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($form_data['logoColors'])): ?>
                        <div class="field-group">
                            <div class="field-label">Logo Colors & Symbols</div>
                            <div class="field-value"><?php echo nl2br(htmlspecialchars($form_data['logoColors'])); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="field-group">
                        <div class="field-label">Brand Guidelines</div>
                        <div class="field-value"><?php echo displayValue($form_data['brandGuidelines'] ?? 'Not Provided'); ?></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Brand Voice/Tone</div>
                        <div class="field-value">
                            <?php 
                            $brandVoice = $form_data['brandVoice'] ?? '';
                            if (!empty($brandVoice)) {
                                echo '<span style="background: #e8f5e8; padding: 3px 8px; border-radius: 4px; text-transform: capitalize; font-weight: 500;">' . htmlspecialchars($brandVoice) . '</span>';
                            } else {
                                echo 'Not Provided';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Company/GST/Billing Details -->
                    <div class="field-group" style="margin-top: 20px;">
                        <div class="field-label">Company / GST / Billing Details</div>
                        <div class="field-value">
                            <?php 
                            $addCompanyDetails = $form_data['addCompanyDetails'] ?? '';
                            if ($addCompanyDetails === 'yes') {
                                echo '<span style="background: #d4edda; color: #155724; padding: 3px 8px; border-radius: 4px; font-weight: 500;">✓ Provided</span>';
                            } else {
                                echo '<span style="background: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 4px; font-weight: 500;">✗ Not Provided</span>';
                            }
                            ?>
                        </div>
                    </div>

                    <?php if ($addCompanyDetails === 'yes'): ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px; border: 1px solid #dee2e6;">
                        <div class="field-grid">
                            <div class="field-group">
                                <div class="field-label">Company Code</div>
                                <div class="field-value"><?php echo displayValue($form_data['companyCode'] ?? 'Not Provided'); ?></div>
                            </div>
                            <div class="field-group">
                                <div class="field-label">GST / VAT Number</div>
                                <div class="field-value"><?php echo displayValue($form_data['gstNumber'] ?? 'Not Provided'); ?></div>
                            </div>
                        </div>
                        <div class="field-grid">
                            <div class="field-group">
                                <div class="field-label">Business Registration Number</div>
                                <div class="field-value"><?php echo displayValue($form_data['registrationNumber'] ?? 'Not Provided'); ?></div>
                            </div>
                            <div class="field-group">
                                <div class="field-label">Bank Account Number / IBAN</div>
                                <div class="field-value"><?php echo displayValue($form_data['bankAccount'] ?? 'Not Provided'); ?></div>
                            </div>
                        </div>
                        <div class="field-grid">
                            <div class="field-group">
                                <div class="field-label">Bank Name</div>
                                <div class="field-value"><?php echo displayValue($form_data['bankName'] ?? 'Not Provided'); ?></div>
                            </div>
                            <div class="field-group">
                                <div class="field-label">IFSC / SWIFT Code</div>
                                <div class="field-value"><?php echo displayValue($form_data['ifscCode'] ?? 'Not Provided'); ?></div>
                            </div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Company Address</div>
                            <div class="field-value"><?php echo displayValue($form_data['companyAddress'] ?? 'Not Provided'); ?></div>
                        </div>
                        <div class="field-grid">
                            <div class="field-group">
                                <div class="field-label">City</div>
                                <div class="field-value"><?php echo displayValue($form_data['city'] ?? 'Not Provided'); ?></div>
                            </div>
                            <div class="field-group">
                                <div class="field-label">State</div>
                                <div class="field-value"><?php echo displayValue($form_data['state'] ?? 'Not Provided'); ?></div>
                            </div>
                        </div>
                        <div class="field-grid">
                            <div class="field-group">
                                <div class="field-label">Country</div>
                                <div class="field-value"><?php echo displayValue($form_data['country'] ?? 'Not Provided'); ?></div>
                            </div>
                            <div class="field-group">
                                <div class="field-label">Pincode</div>
                                <div class="field-value"><?php echo displayValue($form_data['pincode'] ?? 'Not Provided'); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content Collection Subsection -->
                <div class="subsection">
                    <h4 style="color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 8px; margin-bottom: 18px;">📄 CONTENT COLLECTION</h4>
                    <div class="field-group">
                        <div class="field-label">Content Ready Status</div>
                        <div class="field-value"><?php echo displayValue($form_data['contentReady'] ?? 'Not Provided'); ?></div>
                    </div>
                </div>

                
                <!-- Page Structure Information -->
                <div class="field-group">
                    <div class="field-label">Header Pages</div>
                    <div class="field-value"><?php echo displayValue(implode(', ', $form_data['headerPages'] ?? [])); ?></div>
                </div>

                <div class="field-group">
                    <div class="field-label">Footer Pages</div>
                    <div class="field-value"><?php echo displayValue(implode(', ', $form_data['footerPages'] ?? [])); ?></div>
                </div>

                <!-- Page Contents -->
                <?php if (!empty($form_data['pageContents']) && is_array($form_data['pageContents'])): ?>
                    <div class="field-group">
                        <div class="field-label">Page Contents</div>
                        <div class="field-value">
                            <?php foreach ($form_data['pageContents'] as $pageName => $content):
                                // Check if this is the contact page with form builder
                                $isContactForm = (strtolower($pageName) === 'contact');
                            ?>
                                <div class="content-card">
                                    <div style="border-bottom: 2px solid #3498db; padding-bottom: 12px; margin-bottom: 18px;">
                                        <h4 class="content-card__title" style="text-transform: capitalize;">
                                            <?php echo htmlspecialchars($pageName); ?> Page
                                        </h4>
                                    </div>

                                    <?php if ($isContactForm): ?>
                                        <!-- Contact Page Copy -->
                                        <?php if (!empty($form_data['pageHeadings'][$pageName]) || !empty($form_data['pageSubheadings'][$pageName]) || !empty($content)): ?>
                                            <div class="content-card__section content-card__section--copy">
                                                <h5 style="margin: 0 0 14px 0; color: #2c3e50; font-size: 16px;">📝 Contact Page Content</h5>

                                                <?php if (!empty($form_data['pageHeadings'][$pageName])): ?>
                                                    <div style="margin-bottom: 14px;">
                                                        <strong class="content-card__label">Heading:</strong>
                                                        <div class="content-card__value"><?php echo htmlspecialchars($form_data['pageHeadings'][$pageName]); ?></div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($form_data['pageSubheadings'][$pageName])): ?>
                                                    <div style="margin-bottom: 14px;">
                                                        <strong class="content-card__label">Subheading:</strong>
                                                        <div class="content-card__value">
                                                            <?php echo nl2br(htmlspecialchars($form_data['pageSubheadings'][$pageName])); ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($content)): ?>
                                                    <div style="margin-bottom: 8px;">
                                                        <strong class="content-card__label">Content:</strong>
                                                        <div class="content-card__value">
                                                            <?php echo nl2br(htmlspecialchars($content)); ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Contact Form Settings -->
                                        <div class="content-card__section content-card__section--settings">
                                            <h5 style="margin: 0 0 14px 0; color: #2c3e50; font-size: 16px;">📧 Contact Form Configuration</h5>

                                            <?php if (!empty($form_data['contactFormTitle'])): ?>
                                                <div style="margin-bottom: 14px;">
                                                    <strong class="content-card__label">Form Title:</strong>
                                                    <div class="content-card__value"><?php echo htmlspecialchars($form_data['contactFormTitle']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($form_data['contactFormSubmitText'])): ?>
                                                <div style="margin-bottom: 14px;">
                                                    <strong class="content-card__label">Submit Button:</strong>
                                                    <div class="content-card__value"><?php echo htmlspecialchars($form_data['contactFormSubmitText']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($form_data['contactFormThankYou'])): ?>
                                                <div style="margin-bottom: 14px;">
                                                    <strong class="content-card__label">Thank You Message:</strong>
                                                    <div class="content-card__value" style="font-style: italic;">
                                                        <?php echo nl2br(htmlspecialchars($form_data['contactFormThankYou'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($form_data['contactFormAdminEmail'])): ?>
                                                <div style="margin-bottom: 14px;">
                                                    <strong class="content-card__label">Admin Email:</strong>
                                                    <div class="content-card__value"><?php echo htmlspecialchars($form_data['contactFormAdminEmail']); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (empty($form_data['contactFormTitle']) && empty($form_data['contactFormSubmitText']) && empty($form_data['contactFormThankYou']) && empty($form_data['contactFormAdminEmail'])): ?>
                                                <div style="color: #7f8c8d; font-style: italic;">No contact form settings provided.</div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Contact Form Fields -->
                                        <div style="margin-bottom: 20px;">
                                            <h5 style="margin: 0 0 14px 0; color: #2c3e50; font-size: 14px;">📝 Form Fields</h5>
                                            <div class="content-badge-grid">
                                                <?php
                                                $contactFields = [
                                                    'name' => ['icon' => '👤', 'label' => 'Name'],
                                                    'email' => ['icon' => '✉️', 'label' => 'Email'],
                                                    'phone' => ['icon' => '📞', 'label' => 'Phone'],
                                                    'message' => ['icon' => '📝', 'label' => 'Message']
                                                ];
                                                foreach ($contactFields as $fieldKey => $fieldInfo):
                                                    $fieldName = "contactField_{$fieldKey}";
                                                    $isEnabled = !empty($form_data[$fieldName]) && $form_data[$fieldName] === 'yes';
                                                ?>
                                                    <div style="padding: 12px 14px; background: <?php echo $isEnabled ? '#d4edda' : '#f8d7da'; ?>; border-radius: 12px; text-align: center;">
                                                        <span style="font-size: 16px;"><?php echo $fieldInfo['icon']; ?></span>
                                                        <div style="font-size: 14px; color: <?php echo $isEnabled ? '#155724' : '#721c24'; ?>; font-weight: 600; margin-top: 4px;">
                                                            <?php echo $fieldInfo['label']; ?>: <?php echo $isEnabled ? '✓ Yes' : '✗ No'; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Custom Fields -->
                                        <?php
                                        // Check for custom fields
                                        $customFields = [];
                                        foreach ($form_data as $key => $value) {
                                            if (preg_match('/^contactCustomField_(\d+)_label$/', $key, $matches)) {
                                                $index = $matches[1];
                                                $label = $value;
                                                $type = $form_data["contactCustomField_{$index}_type"] ?? 'text';
                                                $customFields[] = ['label' => $label, 'type' => $type];
                                            }
                                        }
                                        if (!empty($customFields)):
                                        ?>
                                            <div style="margin-bottom: 15px;">
                                                <h5 style="margin: 0 0 14px 0; color: #2c3e50; font-size: 14px;">🔧 Custom Fields</h5>
                                                <ul style="list-style: none; padding: 0; margin: 0;">
                                                    <?php foreach ($customFields as $customField): ?>
                                                        <li style="padding: 12px 14px; margin-bottom: 10px; background: white; border-radius: 10px; border: 1px solid #dee2e6;">
                                                            <strong style="font-size: 14px;"><?php echo htmlspecialchars($customField['label']); ?></strong>
                                                            <span style="color: #6c757d; font-size: 14px; margin-left: 8px;">(<?php echo htmlspecialchars($customField['type']); ?>)</span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <!-- Regular Page Content -->
                                        <?php if (!empty($form_data['pageHeadings'][$pageName])): ?>
                                            <div style="margin-bottom: 14px;">
                                                <strong class="content-card__label">Heading:</strong>
                                                <div class="content-card__value" style="font-weight: 600;">
                                                    <?php echo htmlspecialchars($form_data['pageHeadings'][$pageName]); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($form_data['pageSubheadings'][$pageName])): ?>
                                            <div style="margin-bottom: 14px;">
                                                <strong class="content-card__label">Subheading:</strong>
                                                <div class="content-card__value">
                                                    <?php echo htmlspecialchars($form_data['pageSubheadings'][$pageName]); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($content)): ?>
                                            <div style="margin-bottom: 16px;">
                                                <strong class="content-card__label">Content:</strong>
                                                <div class="content-card__value">
                                                    <?php echo nl2br(htmlspecialchars($content)); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($form_data['pageImages'][$pageName])): ?>
                                            <div style="margin-bottom: 16px;">
                                                <strong class="content-card__label">Page Image:</strong>
                                                <div style="margin-top: 8px;">
                                                    <?php
                                                    $imageData = $form_data['pageImages'][$pageName];
                                                    if ((is_array($imageData) && !empty($imageData['data'])) || (is_string($imageData) && !empty($imageData))):
                                                    ?>
                                                        <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                                        <span style="font-size: 14px; color: #6c757d;">
                                                                File: <?php echo htmlspecialchars(is_array($imageData) ? ($imageData['fileName'] ?? 'Unknown') : ($pageName . '.jpg')); ?>
                                                            </span>
                                        <a href="../download_asset.php?client_id=<?php echo urlencode($client['client_id']); ?>&type=page-image&page=<?php echo urlencode($pageName); ?>&download=1" style="display: inline-block; padding: 10px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; font-size: 1rem; font-weight: 600;" target="_blank">
                                                                📥 Download Image
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <span style="color: #7f8c8d; font-style: italic;">Image data not available</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Contact Form Configuration (Standalone Section) -->
                <?php if (!empty($form_data['contactFormTitle']) || !empty($form_data['contactFormSubmitText']) || !empty($form_data['contactFormThankYou']) || !empty($form_data['contactFormAdminEmail']) || !empty($form_data['contactField_name']) || !empty($form_data['contactField_email']) || !empty($form_data['contactField_phone']) || !empty($form_data['contactField_message'])): ?>
                    <div class="field-group">
                        <div class="field-label">📧 Contact Form Configuration</div>
                        <div class="field-value">
                            <div class="content-card">
                                <div style="border-bottom: 2px solid #3498db; padding-bottom: 12px; margin-bottom: 18px;">
                                    <h4 class="content-card__title">Contact Page Form Settings</h4>
                                </div>

                                <!-- Contact Form Settings -->
                                <div class="content-card__section content-card__section--settings" style="margin-bottom: 20px;">
                                    <h5 style="margin: 0 0 14px 0; color: #2c3e50; font-size: 14px;">Form Settings</h5>

                                    <?php if (!empty($form_data['contactFormTitle'])): ?>
                                        <div style="margin-bottom: 10px;">
                                            <strong class="content-card__label">Form Title:</strong>
                                            <div class="content-card__value"><?php echo htmlspecialchars($form_data['contactFormTitle']); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($form_data['contactFormSubmitText'])): ?>
                                        <div style="margin-bottom: 10px;">
                                            <strong class="content-card__label">Submit Button:</strong>
                                            <div class="content-card__value"><?php echo htmlspecialchars($form_data['contactFormSubmitText']); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($form_data['contactFormThankYou'])): ?>
                                        <div style="margin-bottom: 10px;">
                                            <strong class="content-card__label">Thank You Message:</strong>
                                            <div class="content-card__value" style="font-style: italic;">
                                                <?php echo nl2br(htmlspecialchars($form_data['contactFormThankYou'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($form_data['contactFormAdminEmail'])): ?>
                                        <div style="margin-bottom: 10px;">
                                            <strong class="content-card__label">Admin Email:</strong>
                                            <div class="content-card__value"><?php echo htmlspecialchars($form_data['contactFormAdminEmail']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Contact Form Fields -->
                                <div style="margin-bottom: 20px;">
                                    <h5 style="margin: 0 0 14px 0; color: #2c3e50; font-size: 14px;">📝 Form Fields</h5>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                                        <?php
                                        $contactFields = [
                                            'name' => ['icon' => '👤', 'label' => 'Name'],
                                            'email' => ['icon' => '✉️', 'label' => 'Email'],
                                            'phone' => ['icon' => '📞', 'label' => 'Phone'],
                                            'message' => ['icon' => '📝', 'label' => 'Message']
                                        ];
                                        foreach ($contactFields as $fieldKey => $fieldInfo):
                                            $fieldName = "contactField_{$fieldKey}";
                                            $isEnabled = !empty($form_data[$fieldName]) && $form_data[$fieldName] === 'yes';
                                        ?>
                                            <div style="padding: 12px 14px; background: <?php echo $isEnabled ? '#d4edda' : '#f8d7da'; ?>; border-radius: 12px; text-align: center;">
                                                <span style="font-size: 16px;"><?php echo $fieldInfo['icon']; ?></span>
                                                <div style="font-size: 14px; color: <?php echo $isEnabled ? '#155724' : '#721c24'; ?>; font-weight: 600; margin-top: 4px;">
                                                    <?php echo $fieldInfo['label']; ?>: <?php echo $isEnabled ? '✓ Yes' : '✗ No'; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Custom Fields -->
                                <?php
                                // Check for custom fields
                                $customFields = [];
                                foreach ($form_data as $key => $value) {
                                    if (preg_match('/^contactCustomField_(\d+)_label$/', $key, $matches)) {
                                        $index = $matches[1];
                                        $label = $value;
                                        $type = $form_data["contactCustomField_{$index}_type"] ?? 'text';
                                        $customFields[] = ['label' => $label, 'type' => $type];
                                    }
                                }
                                if (!empty($customFields)):
                                ?>
                                    <div style="margin-bottom: 15px;">
                                        <h5 style="margin: 0 0 14px 0; color: #2c3e50; font-size: 14px;">🔧 Custom Fields</h5>
                                        <ul style="list-style: none; padding: 0; margin: 0;">
                                            <?php foreach ($customFields as $customField): ?>
                                                <li style="padding: 12px 14px; margin-bottom: 10px; background: white; border-radius: 10px; border: 1px solid #dee2e6;">
                                                    <strong style="font-size: 14px;"><?php echo htmlspecialchars($customField['label']); ?></strong>
                                                    <span style="color: #6c757d; font-size: 14px; margin-left: 8px;">(<?php echo htmlspecialchars($customField['type']); ?>)</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Page Attachments -->
                <?php if (!empty($form_data['pageAttachments']) && is_array($form_data['pageAttachments'])): ?>
                    <div class="field-group">
                        <div class="field-label">Page Attachments</div>
                        <div class="field-value">
                            <?php foreach ($form_data['pageAttachments'] as $pageName => $attachment): ?>
                                <div style="margin-bottom: 12px; padding: 12px 14px; background: #f8f9fa; border-radius: 10px;">
                                    <strong style="text-transform: capitalize; font-size: 14px;"><?php echo htmlspecialchars($pageName); ?>:</strong><br>
                                    <?php if (!empty($attachment['data'])): ?>
                                        <a href="../download_asset.php?client_id=<?php echo urlencode($client['client_id']); ?>&type=page-attachment&page=<?php echo urlencode($pageName); ?>&download=1" 
                                           style="color: #28a745; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; padding: 8px 12px; background: #e8f5e8; border-radius: 8px; border: 1px solid #28a745; margin-top: 6px;" target="_blank">
                                            📎 <?php echo htmlspecialchars($attachment['fileName'] ?? 'Download File'); ?>
                                        </a><br>
                                    <?php else: ?>
                                        <span style="color: #28a745;">📎 <?php echo htmlspecialchars($attachment['fileName'] ?? $attachment['name'] ?? 'File uploaded'); ?></span><br>
                                    <?php endif; ?>
                                    <small style="color: #6c757d; font-size: 0.98rem;">Type: <?php echo htmlspecialchars($attachment['fileType'] ?? 'Unknown'); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Content Collection Fields -->
                <?php if (!empty($form_data['sellType']) && in_array($form_data['sellType'], ['services', 'both'])): ?>
                    <div class="field-group">
                        <div class="field-label">Service Categories</div>
                        <div class="field-value"><?php echo displayValue($form_data['serviceCategories'] ?? ''); ?></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Service Portfolio Link</div>
                        <div class="field-value"><?php echo displayValue($form_data['servicePortfolioLink'] ?? ''); ?></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Service Pricing</div>
                        <div class="field-value"><?php echo displayValue($form_data['servicePricing'] ?? ''); ?></div>
                    </div>

                    <?php if (!empty($form_data['serviceDetailsFile'])): ?>
                        <div class="field-group">
                            <div class="field-label">Service Details File</div>
                            <div class="field-value">
                                <?php if (!empty($form_data['serviceDetailsFile_data'])): ?>
                                    <a href="../download_asset.php?client_id=<?php echo urlencode($client['client_id']); ?>&type=service-details&download=1" 
                                       style="color: #007bff; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; padding: 8px 15px; background: #e3f2fd; border-radius: 4px; border: 1px solid #007bff; margin-bottom: 10px;" target="_blank">
                                        📄 Download <?php echo htmlspecialchars($form_data['serviceDetailsFile'] ?? 'Service Details'); ?>
                                    </a><br>
                                <?php endif; ?>
                                <strong>File:</strong> <?php echo displayValue($form_data['serviceDetailsFile'] ?? ''); ?><br>
                                <strong>Type:</strong> <?php echo displayValue($form_data['serviceDetailsFile_type'] ?? ''); ?><br>
                                <em>File uploaded successfully</em>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($form_data['sellType']) && in_array($form_data['sellType'], ['products', 'both'])): ?>
                    <div class="field-group">
                        <div class="field-label">Product Categories</div>
                        <div class="field-value"><?php echo displayValue($form_data['productCategories'] ?? ''); ?></div>
                    </div>
                <?php endif; ?>

                <div class="field-group">
                    <div class="field-label">Additional Notes</div>
                    <div class="field-value"><?php echo displayValue($form_data['additionalNotes'] ?? ''); ?></div>
                </div>
            </div>
        </div>

        <!-- Section 4: Products & Features -->
        <div class="section">
            <div class="section-header">4. Products & Features</div>
            <div class="section-content">
                <div class="field-group">
                    <div class="field-label">Business Type</div>
                    <div class="field-value"><?php echo displayValue($form_data['sellType'] ?? ''); ?></div>
                </div>

                <?php if (!empty($form_data['sellType']) && in_array($form_data['sellType'], ['products', 'both'])): ?>
                    <div class="field-group">
                        <div class="field-label">Product Information</div>
                        <div class="field-value">
                            <?php if (!empty($form_data['productCategories'])): ?>
                                <strong>Categories:</strong> <?php echo displayValue($form_data['productCategories']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($form_data['productDriveLink'])): ?>
                                <strong>Product Drive Link:</strong> <a href="<?php echo htmlspecialchars($form_data['productDriveLink']); ?>" target="_blank">View Products</a><br>
                            <?php endif; ?>
                            <?php if (!empty($form_data['filterAttributes'])): ?>
                                <strong>Filter Attributes:</strong> <?php echo displayArray($form_data['filterAttributes']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                
                <div class="field-group">
                    <div class="field-label">Custom Features</div>
                    <div class="field-value">
                        <?php if (!empty($form_data['customFeatures'])): ?>
                            <div class="checkbox-list">
                                <?php 
                                $features = is_array($form_data['customFeatures']) ? $form_data['customFeatures'] : [$form_data['customFeatures']];
                                foreach ($features as $feature): ?>
                                    <span class="checkbox-item"><?php echo htmlspecialchars(ucfirst($feature)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <?php echo displayValue(''); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($form_data['otherFeatures'])): ?>
                    <div class="field-group">
                        <div class="field-label">Other Features</div>
                        <div class="field-value"><?php echo displayValue($form_data['otherFeatures']); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Ecommerce Setup Subsection -->
                <div class="subsection">
                    <h4 style="color: #2c3e50; border-bottom: 2px solid #e67e22; padding-bottom: 8px; margin-bottom: 18px;">🛒 ECOMMERCE SETUP</h4>
                    
                    <div class="field-group">
                        <div class="field-label">E-commerce Website</div>
                        <div class="field-value"><?php echo displayValue($form_data['isEcommerce'] ?? ''); ?></div>
                    </div>

                    <?php if (!empty($form_data['isEcommerce']) && $form_data['isEcommerce'] === 'yes'): ?>
                        <div class="field-group">
                            <div class="field-label">Payment Methods</div>
                            <div class="field-value">
                                <?php 
                                $payment_methods = $form_data['paymentMethods'] ?? [];
                                if (!is_array($payment_methods)) $payment_methods = [$payment_methods];
                                
                                if (!empty($payment_methods)) {
                                    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                                    foreach ($payment_methods as $method) {
                                        $method_name = ucfirst(str_replace('-', ' ', $method));
                                        echo '<span class="content-chip" style="background: #e8f5e9; color: #2e7d32;">' . htmlspecialchars($method_name) . '</span>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo displayValue('');
                                }
                                ?>
                            </div>
                        </div>

                        <?php if (!empty($form_data['razorpayKeyId'])): ?>
                        <div class="field-group">
                            <div class="field-label">Razorpay Key ID</div>
                            <div class="field-value"><?php echo htmlspecialchars($form_data['razorpayKeyId']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($form_data['stripeKey'])): ?>
                        <div class="field-group">
                            <div class="field-label">Stripe Publishable Key</div>
                            <div class="field-value"><?php echo htmlspecialchars($form_data['stripeKey']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($form_data['paypalEmail'])): ?>
                        <div class="field-group">
                            <div class="field-label">PayPal Email</div>
                            <div class="field-value"><?php echo htmlspecialchars($form_data['paypalEmail']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($form_data['otherPaymentMethod'])): ?>
                        <div class="field-group">
                            <div class="field-label">Other Payment Method</div>
                            <div class="field-value"><?php echo displayValue($form_data['otherPaymentMethod']); ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="field-group">
                            <div class="field-label">Currencies Accepted</div>
                            <div class="field-value">
                                <?php 
                                $currencies = $form_data['currencies'] ?? [];
                                if (!is_array($currencies)) $currencies = [$currencies];
                                
                                if (!empty($currencies)) {
                                    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                                    foreach ($currencies as $currency) {
                                        echo '<span class="content-chip" style="background: #fff3e0; color: #e65100;">' . htmlspecialchars($currency) . '</span>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo displayValue('');
                                }
                                ?>
                            </div>
                        </div>

                        <?php if (!empty($form_data['otherCurrencyText'])): ?>
                        <div class="field-group">
                            <div class="field-label">Other Currency</div>
                            <div class="field-value"><?php echo displayValue($form_data['otherCurrencyText']); ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="field-group">
                            <div class="field-label">Tax Information</div>
                            <div class="field-value">
                                <?php if (!empty($form_data['taxRegion'])): ?>
                                    <strong>Tax Region:</strong> <?php echo displayValue($form_data['taxRegion']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($form_data['taxRate'])): ?>
                                    <strong>Tax Rate:</strong> <?php echo displayValue($form_data['taxRate']); ?>%
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="field-group">
                            <div class="field-label">Shipping Information</div>
                            <div class="field-value">
                                <?php if (!empty($form_data['shippingType'])): ?>
                                    <strong>Shipping Type:</strong> <?php echo displayValue(ucfirst(str_replace('-', ' ', $form_data['shippingType']))); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($form_data['flatRate'])): ?>
                                    <strong>Flat Rate:</strong> <?php echo displayValue($form_data['flatRate']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($form_data['shippingZones'])): ?>
                                    <strong>Shipping Zones:</strong> <?php echo nl2br(htmlspecialchars($form_data['shippingZones'])); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($form_data['shippingRates'])): ?>
                                    <strong>Shipping Rates:</strong> <?php echo nl2br(htmlspecialchars($form_data['shippingRates'])); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($form_data['deliveryTimeframes'])): ?>
                                    <strong>Delivery Timeframes:</strong> <?php echo nl2br(htmlspecialchars($form_data['deliveryTimeframes'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="field-group">
                            <div class="field-label">Inventory Type</div>
                            <div class="field-value"><?php echo displayValue(ucfirst($form_data['inventoryType'] ?? '')); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section 5: Marketing & SEO -->
        <div class="section">
            <div class="section-header">5. Marketing & SEO</div>
            <div class="section-content">
                <div class="field-group">
                    <div class="field-label">SEO Information</div>
                    <div class="field-value">
                        <?php if (!empty($form_data['businessDescription'])): ?>
                            <strong>Business Description:</strong> <?php echo displayValue($form_data['businessDescription']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['keywords'])): ?>
                            <strong>Keywords:</strong> <?php echo displayValue($form_data['keywords']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['competitors'])): ?>
                            <strong>Competitors:</strong> <?php echo displayValue($form_data['competitors']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="field-group">
                    <div class="field-label">Google Analytics</div>
                    <div class="field-value"><?php echo displayValue($form_data['hasAnalytics'] ?? ''); ?></div>
                </div>

                <!-- Social Media Subsection -->
                <div class="subsection">
                    <h4 style="color: #2c3e50; border-bottom: 2px solid #e67e22; padding-bottom: 8px; margin-bottom: 18px;">📱 SOCIAL MEDIA</h4>
                    <div class="field-group">
                        <div class="field-label">Social Media Links</div>
                        <div class="field-value">
                            <?php 
                            $social_platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'pinterest', 'tiktok'];
                            $has_social = false;
                            foreach ($social_platforms as $platform) {
                                $field_name = $platform . 'Url';
                                if (!empty($form_data[$field_name])) {
                                    echo '<div style="margin-bottom: 10px; font-size: 14px;"><strong>' . ucfirst($platform) . ':</strong> <a href="' . htmlspecialchars($form_data[$field_name]) . '" target="_blank" style="color: #3498db; text-decoration: none;">🔗 ' . htmlspecialchars($form_data[$field_name]) . '</a></div>';
                                    $has_social = true;
                                }
                            }
                            if (!$has_social) {
                                echo '<span style="color: #7f8c8d; font-style: italic;">No social media links provided</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php if (!empty($form_data['otherSocialMedia'])): ?>
                        <div class="field-group">
                            <div class="field-label">Other Social Media</div>
                            <div class="field-value"><?php echo nl2br(htmlspecialchars($form_data['otherSocialMedia'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section 6: Final Details -->
        <div class="section">
            <div class="section-header">6. Final Details</div>
            <div class="section-content">
                <div class="field-group">
                    <div class="field-label">Contact Information</div>
                    <div class="field-value">
                        <?php if (!empty($form_data['contactName'])): ?>
                            <strong>Contact Name:</strong> <?php echo displayValue($form_data['contactName']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['contactEmail'])): ?>
                            <strong>Contact Email:</strong> <?php echo displayValue($form_data['contactEmail']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($form_data['contactPhone'])): ?>
                            <strong>Contact Phone:</strong> <?php echo displayValue($form_data['contactPhone']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-label">Custom Features & Forms</div>
                    <div class="field-value">
                        <?php
                        $custom_features = $form_data['customFeatures'] ?? [];
                        if (!is_array($custom_features)) $custom_features = [$custom_features];
                        
                        if (!empty($custom_features)) {
                            echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                            foreach ($custom_features as $feature) {
                                echo '<span class="content-chip" style="background: #e3f2fd; color: #1976d2;">' . htmlspecialchars(ucfirst($feature)) . '</span>';
                            }
                            echo '</div>';
                        } else {
                            echo displayValue('');
                        }
                        ?>
                    </div>
                </div>

                <?php if (!empty($form_data['otherFeatures'])): ?>
                <div class="field-group">
                    <div class="field-label">Other Custom Features</div>
                    <div class="field-value"><?php echo displayValue($form_data['otherFeatures']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($form_data['integrations'])): ?>
                <div class="field-group">
                    <div class="field-label">Third-party Integrations</div>
                    <div class="field-value"><?php echo displayValue($form_data['integrations']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($form_data['formRequirements'])): ?>
                <div class="field-group">
                    <div class="field-label">Form Requirements</div>
                    <div class="field-value"><?php echo displayValue($form_data['formRequirements']); ?></div>
                </div>
                <?php endif; ?>

                <div class="field-group">
                    <div class="field-label">Website Structure</div>
                    <div class="page-structure">
                        <?php
                        $header_pages = $form_data['headerPages'] ?? [];
                        $footer_pages = $form_data['footerPages'] ?? [];
                        
                        if (!is_array($header_pages)) $header_pages = [$header_pages];
                        if (!is_array($footer_pages)) $footer_pages = [$footer_pages];
                        
                        $all_pages = [];
                        foreach ($header_pages as $page) {
                            $all_pages[] = ['name' => ucfirst($page), 'type' => 'Header'];
                        }
                        foreach ($footer_pages as $page) {
                            $all_pages[] = ['name' => ucfirst($page), 'type' => 'Footer'];
                        }
                        
                        if (!empty($all_pages)) {
                            foreach ($all_pages as $page) {
                                echo '<div class="page-item">';
                                echo '<span class="page-name">' . htmlspecialchars($page['name']) . '</span>';
                                echo '<span class="page-type">' . htmlspecialchars($page['type']) . '</span>';
                                echo '</div>';
                            }
                        } else {
                            echo displayValue('');
                        }
                        ?>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-label">Additional Notes</div>
                    <div class="field-value"><?php echo displayValue($form_data['additionalNotes'] ?? ''); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- html2pdf library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <script>
        function generatePDF(evt) {
            const element = document.getElementById('pdfContent');
            const btn = evt?.currentTarget || evt?.target || document.querySelector('.btn-pdf');
            const originalText = btn ? btn.innerHTML : '';
            const opt = {
                margin: [15, 15, 15, 15], // top, left, bottom, right
                filename: '<?php echo htmlspecialchars($client['client_id']); ?>_client_details.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };

            // Show loading state
            if (btn) {
                btn.innerHTML = 'Generating PDF...';
                btn.disabled = true;
            }

            html2pdf().set(opt).from(element).save().then(() => {
                // Restore button state
                if (btn) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }).catch((error) => {
                console.error('PDF generation failed:', error);
                alert('PDF generation failed. Please try again.');
                if (btn) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }

        function deleteClient() {
            const clientId = '<?php echo htmlspecialchars($client['client_id']); ?>';
            const btn = event.target;
            const originalText = btn.innerHTML;
            
            // Store for later use
            window.clientToDelete = clientId;
            window.deleteBtn = btn;
            window.originalBtnText = originalText;
            
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            window.clientToDelete = null;
            window.deleteBtn = null;
            window.originalBtnText = null;
        }

        function confirmDelete() {
            if (!window.clientToDelete) return;

            const btn = window.deleteBtn;
            const originalText = window.originalBtnText;
            btn.innerHTML = 'Deleting...';
            btn.disabled = true;

            fetch('delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'client_id=' + encodeURIComponent(window.clientToDelete)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDeleteModal();
                    document.getElementById('successModal').style.display = 'flex';
                } else {
                    alert('Error: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    closeDeleteModal();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete client. Please try again.');
                btn.innerHTML = originalText;
                btn.disabled = false;
                closeDeleteModal();
            });
        }

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
            window.location.href = 'index.php';
        }

    </script>

    <!-- Custom Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-title">
                <span class="modal-icon">⚠️</span>
                Delete Client
            </div>
            <div class="modal-message">
                Are you sure you want to delete this client? This action cannot be undone and all data will be permanently removed.
            </div>
            <div class="modal-actions">
                <button class="btn-modal btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-modal btn-modal-confirm" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal-overlay" id="successModal">
        <div class="modal modal-success">
            <div class="modal-title">
                <span class="modal-icon">✅</span>
                Success
            </div>
            <div class="modal-message">
                Client deleted successfully!
            </div>
            <div class="modal-actions">
                <button class="btn-modal btn-modal-confirm" onclick="closeSuccessModal()">OK</button>
            </div>
        </div>
    </div>
</body>
</html>
