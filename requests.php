<?php
require_once 'app/config/db.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $ref_url = filter_input(INPUT_POST, 'ref_url', FILTER_SANITIZE_URL);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);

    $response = ['status' => 'error', 'message' => 'Something went wrong.'];

    if ($title) {
        try {
            $stmt = $conn->prepare("INSERT INTO requests (title, ref_url, message, status, created_at) VALUES (:title, :ref_url, :message, 'pending', :created_at)");
            $now = date('Y-m-d H:i:s');
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':ref_url', $ref_url);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':created_at', $now);

            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Request submitted successfully.'];
            }
        } catch (PDOException $e) {
            $response = ['status' => 'error', 'message' => 'Database error.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Anime name is required.'];
    }

    // If it's an AJAX request (as per the snippet's user.js which we don't have full control over but the snippet implies AJAX), return JSON.
    // However, the snippet uses `callBackAjaxForm` which usually expects JSON.
    // If not AJAX, we might want to just render the page with a message.

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Basic Page Setup
require_once 'app/config/info.php'; // For $base_url
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Request Anime | Anitaku</title>
        <meta name="description" content="Request anime." />
        <meta name="keywords" content="request anime" />
        <meta name="robots" content="index, follow" />
        <meta name="revisit-after" content="1 days" />
        <base href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/" />

        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <link rel="canonical" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/requests" />
        <link rel="shortcut icon" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/assets/img/favicon.ico" type="image/x-icon" />

        <link rel="stylesheet" type="text/css" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css" />
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    <body>
        <div id="wrapper_inside">
            <div id="wrapper">
                <div id="wrapper_bg">
                    <?php require_once 'app/views/partials/header.php'; ?>

                    <section class="content">
                        <div class="main_body">
                            <div class="anime_name ongoing">
                                <div class="anime_name_img_ongoing">
                                </div>
                                <h2>
                                    Request Anime
                                </h2>
                            </div>
                            <div class="page_content page-content-center">
                                <p style="color: #ffc119;">If you can't find your favourite anime in our library, please submit a request. We will try to make it available as soon as possible.</p>

                                <?php if (isset($response) && !(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')): ?>
                                    <div class="alert alert-<?= $response['status'] == 'success' ? 'success' : 'danger' ?>" style="display: block; color: <?= $response['status'] == 'success' ? 'green' : 'red' ?>;">
                                        <?= htmlspecialchars($response['message'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>

                                <form method='post' class='ajax-form'>
                                    <div class="alert alert-danger" style="display: none;"></div>
                                    <div class="alert alert-success" style="display: none;"></div>
                                    <div class="form-group">
                                            <label>Anime name</label>
                                        <input type="text" class="form-control" name="title" placeholder="Anime name" required="">
                                    </div>
                                    <div class="form-group">
                                    <label>Link</label>
                                        <input type="text" class="form-control" name="ref_url" placeholder="Link to MAL/ anidb/ anilist or any if possible">
                                    </div>
                                    <div class="form-group">
                                    <label>Message</label>
                                        <textarea class="form-control" name="message" placeholder="More details about it if possible" rows="3"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <!-- Captcha placeholder as requested by snippet structure, but logic skipped for now -->
                                        <span class="captcha"></span>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-lg fw-bold btn-primary w-100">
                                            Submit
                                        </button>
                                    </div>
                                    <div class="loading" style="display: none;"></div>
                                </form>
                            </div>
                        </div>
                    </section>

                    <div class="clr"></div>
                    <?php require_once 'app/views/partials/footer.php'; ?>
                </div>
            </div>
        </div>

        <script src="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/assets/js/main.js"></script>
        <script>
            // Simple JS to handle AJAX if main.js doesn't cover it fully for this specific form class or if we need to polyfill
            $(document).ready(function() {
                $('.ajax-form').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var formData = form.serialize();

                    form.find('.alert').hide();
                    form.find('.loading').show();

                    $.post('requests.php', formData, function(data) {
                        form.find('.loading').hide();
                        if (data.status === 'success') {
                            form.find('.alert-success').text(data.message).show();
                            form[0].reset();
                        } else {
                            form.find('.alert-danger').text(data.message).show();
                        }
                    }, 'json').fail(function() {
                        form.find('.loading').hide();
                        form.find('.alert-danger').text("An error occurred. Please try again.").show();
                    });
                });
            });
        </script>
    </body>
</html>
