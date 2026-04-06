<?php
/**
 * OS Chennai - Submit Quote API
 */
require_once 'connect.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method !== 'POST') {
    echo json_encode(['error' => 'Invalid request method. Only POST allowed.']);
    exit;
}

// Get form data
// Handle both regular form data and JSON data
$data = [];
$contentType = $_SERVER["CONTENT_TYPE"] ?? "";

if (strpos($contentType, "application/json") !== false) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
} else {
    $data = $_POST;
}

// Validation
$requiredFields = ['name', 'email', 'phone', 'message'];
$errors = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $errors[] = ucfirst($field) . " is required.";
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode(" ", $errors)]);
    exit;
}

// Extra field: subject (optional but useful)
$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$subject = !empty($data['subject']) ? trim($data['subject']) : "New Quote Request";
$message = trim($data['message']);

try {
    // 1. Insert into Database
    $projectId = $data['project_id'] ?? 'os-chennai';
    try {
        $sql = "INSERT INTO quotes (name, email, phone, subject, message, project_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $phone, $subject, $message, $projectId]);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') {
            // Auto-create table if missing
            $pdo->exec("CREATE TABLE IF NOT EXISTS quotes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                email VARCHAR(255),
                phone VARCHAR(50),
                subject VARCHAR(255),
                message TEXT,
                project_id VARCHAR(50) DEFAULT 'nutpa',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            // Retry insert
            $stmt = $pdo->prepare("INSERT INTO quotes (name, email, phone, subject, message, project_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $subject, $message, $projectId]);
        } else if ($e->getCode() === '42S22') {
            // Column missing, try to add it
            try {
                $pdo->exec("ALTER TABLE quotes ADD COLUMN project_id VARCHAR(50) DEFAULT 'nutpa'");
                // Retry insert
                $stmt = $pdo->prepare("INSERT INTO quotes (name, email, phone, subject, message, project_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $subject, $message, $projectId]);
            } catch (Exception $e2) {
                throw $e; // Throw original if alter fails
            }
        } else {
            throw $e;
        }
    }

    // 2. Fetch Admin Settings & Enquiry Ref
    $quoteId = $pdo->lastInsertId();
    // Generate a unique reference similar to the image (UUID-like but concise)
    $enquiryRef = strtoupper(substr(md5($quoteId . "salt"), 0, 8)) . "-" . strtoupper(substr(md5(time()), 0, 4)) . "-" . strtoupper(substr(md5($name), 0, 4)) . "-" . strtoupper(substr(md5($subject), 0, 12));
    
    $adminEmail = "sales@nutpa.com"; // Default fallback
    $siteName = "OS Chennai"; // Default fallback
    $contactPhone = "+91 99404 28882";
    $siteLogo = "assets/logo.png";

    try {
        $stmt = $pdo->prepare("SELECT site_name, contact_email, contact_phone, site_logo FROM site_settings WHERE project_id = ? LIMIT 1");
        $stmt->execute([$projectId]);
        $settings = $stmt->fetch();
        if ($settings) {
            if (!empty($settings['contact_email'])) $adminEmail = $settings['contact_email'];
            if (!empty($settings['site_name'])) $siteName = $settings['site_name'];
            if (!empty($settings['contact_phone'])) $contactPhone = $settings['contact_phone'];
            if (!empty($settings['site_logo'])) $siteLogo = $settings['site_logo'];
        }
    } catch (Exception $e) { /* ignore settings fetch errors */ }

    // Normalize Logo URL
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'oschennai.in';
    if (strpos($siteLogo, 'http') !== 0) {
        $siteLogo = $protocol . "://" . $host . "/" . ltrim($siteLogo, '/');
    }

    // 3. Construct Email Notification (To Admin)
    require_once 'SmtpHelper.php';

    $to = $adminEmail;
    $emailSubject = "[$siteName] New Quote Request: " . $subject;

    // HTML Email Body (Admin)
    $body = "
    <html>
    <head>
        <title>New Quote Request</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1e293b; background: #f8fafc; padding: 20px; }
            .container { background: #ffffff; padding: 32px; border: 1px solid #e2e8f0; border-radius: 16px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .header { border-bottom: 2px solid #3b82f6; padding-bottom: 16px; margin-bottom: 24px; }
            .header h2 { color: #1e3a8a; margin: 0; font-size: 24px; }
            .field { margin-bottom: 20px; }
            .label { font-weight: 800; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 4px; }
            .value { font-size: 15px; color: #1e293b; font-weight: 600; white-space: pre-wrap; }
            .footer { font-size: 12px; color: #94a3b8; margin-top: 32px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 24px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'><h2>New Inquiry Received</h2></div>
            <div class='content'>
                <div class='field'><span class='label'>Customer Name</span><div class='value'>$name</div></div>
                <div class='field'><span class='label'>Email Address</span><div class='value'>$email</div></div>
                <div class='field'><span class='label'>Phone Number</span><div class='value'>$phone</div></div>
                <div class='field'><span class='label'>Subject</span><div class='value'>$subject</div></div>
                <div class='field'><span class='label'>Message</span><div class='value'>$message</div></div>
                <div class='field'><span class='label'>Reference ID</span><div class='value' style='font-family:monospace; color:#3b82f6;'>#$enquiryRef</div></div>
            </div>
            <div class='footer'>Sent automatically via $siteName. Reference: $enquiryRef</div>
        </div>
    </body>
    </html>
    ";

    // Send Email via Zoho SMTP
    $mailSent = false;
    try {
        $smtp = new SimpleSmtp('smtp.zoho.in', 465, 'support@rentla.in', 'DmYTCmEFuVYH');
        $headersExtra = [
            "Reply-To" => "$name <$email>",
            "X-Mailer" => "PHP/" . phpversion()
        ];
        $mailSent = $smtp->send($to, $emailSubject, $body, $headersExtra);

        // 4. Send Auto-Reply to Customer (Premium Template based on user image)
        try {
           $customerSubject = "We Received Your Enquiry - $siteName";
           $customerBody = "
           <html>
           <head>
               <meta charset='UTF-8'>
               <style>
                   @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
                   body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background-color: #f3f4f6; color: #1f2937; -webkit-font-smoothing: antialiased; }
                   .wrapper { padding: 40px 10px; background-color: #f3f4f6; }
                   .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
                   .header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 40px 35px; color: #ffffff; }
                   .logo { height: 45px; margin-bottom: 25px; }
                   .site-name { font-weight: 800; font-size: 22px; letter-spacing: -0.5px; opacity: 0.95; }
                   .title { margin: 0; font-size: 28px; font-weight: 700; line-height: 1.2; letter-spacing: -0.02em; }
                   .subtitle { margin-top: 10px; font-size: 15px; opacity: 0.85; line-height: 1.5; }
                   .content { padding: 45px 35px; }
                   .salutation { font-size: 17px; font-weight: 600; color: #111827; margin-bottom: 12px; }
                   .intro { font-size: 15px; line-height: 1.7; color: #4b5563; margin-bottom: 35px; }
                   .ref-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                   .ref-label { font-size: 11px; font-weight: 800; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
                   .ref-value { font-size: 18px; font-weight: 700; color: #111827; font-family: monospace; word-break: break-all; }
                   .help-box { background: #f0f7ff; border: 1px solid #dbeafe; border-radius: 12px; padding: 22px; margin-bottom: 35px; }
                   .help-label { font-size: 12px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
                   .help-text { font-size: 15px; color: #1e40af; line-height: 1.4; }
                   .help-text a { font-weight: 700; color: #2563eb; text-decoration: none; }
                   .benefits { list-style: none; padding: 0; margin-bottom: 40px; }
                   .benefit-item { font-size: 14px; color: #6b7280; margin-bottom: 12px; padding-left: 20px; position: relative; }
                   .benefit-item:before { content: '•'; position: absolute; left: 0; color: #3b82f6; font-weight: bold; }
                   .footer { border-top: 1px solid #f3f4f6; padding-top: 30px; }
                   .regards { font-size: 15px; color: #4b5563; margin: 0; }
                   .author { font-size: 16px; font-weight: 700; color: #1e40af; margin-top: 5px; }
                   .outer-footer { text-align: center; margin-top: 25px; font-size: 12px; color: #9ca3af; }
               </style>
           </head>
           <body>
               <div class='wrapper'>
                   <div class='container'>
                       <div class='header'>
                           <div class='site-name'>
                               " . (!empty($settings['site_logo']) ? "<img src='$siteLogo' alt='$siteName' class='logo'>" : "$siteName") . "
                           </div>
                           <h1 class='title'>We Received Your Enquiry</h1>
                           <div class='subtitle'>Thank you for contacting us. Our team will review your request and get back to you shortly.</div>
                       </div>
                       <div class='content'>
                           <div class='salutation'>Hello $name,</div>
                           <div class='intro'>
                               We have received your enquiry for <strong>$subject</strong>. Your request has been recorded successfully and shared with our team.
                           </div>
                           
                           <div class='ref-box'>
                               <div class='ref-label'>ENQUIRY REFERENCE</div>
                               <div class='ref-value'>$enquiryRef</div>
                           </div>

                           <div class='help-box'>
                               <div class='help-label'>NEED IMMEDIATE HELP?</div>
                               <div class='help-text'>For more information, please contact us at: <a href='tel:$contactPhone'>$contactPhone</a></div>
                           </div>

                           <ul class='benefits'>
                               <li class='benefit-item'>Our team usually responds during business hours.</li>
                               <li class='benefit-item'>For urgent help, you can also reply directly to this email.</li>
                               <li class='benefit-item'>Please keep the enquiry reference handy for faster follow-up.</li>
                           </ul>

                           <div class='footer'>
                               <p class='regards'>Regards,</p>
                               <p class='author'>$siteName Team</p>
                           </div>
                       </div>
                   </div>
                   <div class='outer-footer'>
                       &copy; " . date('Y') . " $siteName. All rights reserved.
                   </div>
               </div>
           </body>
           </html>
           ";
           $smtp->send($email, $customerSubject, $customerBody);
        } catch (Exception $e2) { /* ignore auto-reply failures to not block original success */ }

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log("SMTP Error: " . $errorMsg);
    }

    echo json_encode([
        'success' => true,
        'message' => $mailSent ? 'Quote submitted successfully! Our team will contact you shortly.' : 'Quote saved in database, but email notification failed. Please call us directly.',
        'mail_status' => $mailSent ? 'Sent' : 'Failed'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Submission failed: ' . $e->getMessage()]);
}
?>
