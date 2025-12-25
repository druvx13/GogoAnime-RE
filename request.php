<?php
require_once('./app/config/info.php');
require_once('./app/config/db.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF check (if token implemented in header/session, currently basic)
    // Assuming session is started in header.php or config.

    $title = trim($_POST['title'] ?? '');
    $link = trim($_POST['ref_url'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($title)) {
        try {
            $stmt = $conn->prepare("INSERT INTO requests (title, link, message) VALUES (:title, :link, :message)");
            $stmt->execute([
                'title' => $title,
                'link' => $link,
                'message' => $message
            ]);
            $success = "Request submitted successfully! We will try to make it available as soon as possible.";
        } catch(PDOException $e) {
            $error = "Error submitting request: " . $e->getMessage();
        }
    } else {
        $error = "Anime name is required.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Request Anime - <?=$website_name?></title>
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script type="text/javascript" src="https://cdn.gogocdn.net/files/gogo/js/main.js"></script>
</head>
<body>
    <div id="wrapper_inside">
        <div id="wrapper">
            <div id="wrapper_bg">
                <?php require_once('./app/views/partials/header.php'); ?>

                <section class="content">
                    <div class="main_body">
                        <div class="anime_name ongoing">
                            <div class="anime_name_img_ongoing"></div>
                            <h2>Request Anime</h2>
                        </div>
                        <div class="page_content page-content-center">
                            <p style="color: #ffc119;">If you can't find your favourite anime in our library, please submit a request. We will try to make it available as soon as possible.</p>

                            <?php if($error): ?>
                                <div class="alert alert-danger" style="display: block; color: red; margin: 10px 0; padding: 10px; border: 1px solid red; background: #ffe6e6;"><?=$error?></div>
                            <?php endif; ?>

                            <?php if($success): ?>
                                <div class="alert alert-success" style="display: block; color: green; margin: 10px 0; padding: 10px; border: 1px solid green; background: #e6ffe6;"><?=$success?></div>
                            <?php endif; ?>

                            <form method="post" action="">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px;">Anime name</label>
                                    <input type="text" class="form-control" name="title" placeholder="Anime name" required="" style="width: 100%; padding: 8px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px;">Link</label>
                                    <input type="text" class="form-control" name="ref_url" placeholder="Link to MAL/ anidb/ anilist or any if possible" style="width: 100%; padding: 8px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px;">Message</label>
                                    <textarea class="form-control" name="message" placeholder="More details about it if possible" rows="3" style="width: 100%; padding: 8px;"></textarea>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-lg fw-bold btn-primary w-100" style="padding: 10px 20px; background: #ffc119; border: none; cursor: pointer; color: #000; font-weight: bold;">
                                        Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <?php include('./app/views/partials/footer.php'); ?>
            </div>
        </div>
    </div>
</body>
</html>
