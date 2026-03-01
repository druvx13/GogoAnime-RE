<?php
require_once('./app/config/info.php');
require_once('./app/config/db.php');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    if (!empty($name) && !empty($email) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
        $stmt->execute(['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message]);
        $msg = "<div style='color:green; margin-bottom:10px;'>Message sent successfully!</div>";
    } else {
        $msg = "<div style='color:red; margin-bottom:10px;'>Please fill all fields.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us - <?=$website_name?></title>
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/responsive.css" />
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <style>
        .contact-form { padding: 20px; background: #222; color: #fff; }
        .contact-form input, .contact-form textarea { width: 100%; padding: 10px; margin-bottom: 15px; background: #333; border: 1px solid #444; color: #fff; }
        .contact-form button { padding: 10px 20px; background: #00a651; color: #fff; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div id="wrapper_inside">
        <div id="wrapper">
            <div id="wrapper_bg">
                <?php require_once('./app/views/partials/header.php'); ?>
                <section class="content">
                    <section class="content_left">
                        <div class="main_body">
                            <div class="anime_name contact">
                                <i class="icongec-contact i_pos"></i>
                                <h2>Contact Us</h2>
                            </div>
                            <div class="contact-form">
                                <?=$msg?>
                                <form method="POST">
                                    <label>Name</label>
                                    <input type="text" name="name" required>

                                    <label>Email</label>
                                    <input type="email" name="email" required>

                                    <label>Subject</label>
                                    <input type="text" name="subject" required>

                                    <label>Message</label>
                                    <textarea name="message" rows="5" required></textarea>

                                    <button type="submit">Send Message</button>
                                </form>
                            </div>
                        </div>
                    </section>
                </section>
                <?php include('./app/views/partials/footer.php'); ?>
            </div>
        </div>
    </div>
</body>
</html>
