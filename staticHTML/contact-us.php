<?php
// Fix include paths for router/root execution
$root = dirname(__DIR__); 
require_once($root . '/app/config/info.php');
require_once($root . '/app/config/db.php');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Basic validation
    if (!empty($name) && !empty($email) && !empty($message)) {
        // Disclaimer: No guarantee of response
        try {
            $stmt = $conn->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
            $stmt->execute(['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message]);
            $msg = "<div style='color:green; margin-bottom:10px;'>Message transmitted. This does not constitute an acknowledgement of receipt.</div>";
        } catch (Exception $e) {
             $msg = "<div style='color:red; margin-bottom:10px;'>Transmission failed.</div>";
        }
    } else {
        $msg = "<div style='color:red; margin-bottom:10px;'>All fields are required.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Contact - <?=$website_name?></title>
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <style>
        .contact-form { padding: 20px; background: #222; color: #fff; }
        .contact-form input, .contact-form textarea { width: 100%; padding: 10px; margin-bottom: 15px; background: #333; border: 1px solid #444; color: #fff; }
        .contact-form button { padding: 10px 20px; background: #00a651; color: #fff; border: none; cursor: pointer; }
        .legal-notice { font-size: 0.8em; color: #aaa; margin-top: 20px; border-top: 1px solid #444; padding-top: 10px; }
    </style>
</head>
<body>
    <div id="wrapper_inside">
        <div id="wrapper">
            <div id="wrapper_bg">
                <?php require($root . '/app/views/partials/header.php'); ?>
                <section class="content">
                    <section class="content_left">
                        <div class="main_body">
                            <div class="anime_name contact">
                                <i class="icongec-contact i_pos"></i>
                                <h2>Contact</h2>
                            </div>
                            <div class="contact-form">
                                <?=$msg?>
                                <p>Use the form below to contact the site administration regarding technical issues or inquiries.</p>
                                <form method="POST">
                                    <label>Name</label>
                                    <input type="text" name="name" required>

                                    <label>Email</label>
                                    <input type="email" name="email" required>

                                    <label>Subject</label>
                                    <input type="text" name="subject" required>

                                    <label>Message</label>
                                    <textarea name="message" rows="5" required></textarea>

                                    <button type="submit">Submit Inquiry</button>
                                </form>
                                <div class="legal-notice">
                                    <strong>Notice:</strong> Any communication sent via this form is non-confidential. 
                                    The administration reserves the right to review, retain, or discard messages at its sole discretion. 
                                    Submission of this form does not create a service obligation or guarantee a response.
                                    Do not send sensitive personal information or credentials.
                                </div>
                            </div>
                        </div>
                    </section>
                </section>
                <?php include($root . '/app/views/partials/footer.php'); ?>
            </div>
        </div>
    </div>
</body>
</html>
