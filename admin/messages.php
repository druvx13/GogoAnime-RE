<?php
/**
 * Admin Contact Messages
 *
 * This page displays messages submitted via the "Contact Us" form.
 * Administrators can read inquiries and delete them.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $conn->prepare("DELETE FROM contacts WHERE id = :id")->execute(['id' => $id]);
        $success = "Message deleted.";
        // Refresh page to clear GET param
        header("Location: messages.php");
        exit;
    } catch(PDOException $e) {
        $error = "Error deleting message.";
        error_log("Delete contact message error: " . $e->getMessage());
    }
}

// Fetch Messages
try {
    $stmt = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $messages = [];
    $error = "Failed to load messages.";
}
?>

<h2>Contact Messages</h2>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($messages as $msg): ?>
            <tr>
                <td style="white-space:nowrap;"><?=htmlspecialchars($msg['created_at'])?></td>
                <td><?=htmlspecialchars($msg['name'])?></td>
                <td><a href="mailto:<?=htmlspecialchars($msg['email'])?>"><?=htmlspecialchars($msg['email'])?></a></td>
                <td><?=htmlspecialchars($msg['subject'])?></td>
                <td><?=nl2br(htmlspecialchars($msg['message']))?></td>
                <td>
                    <a href="?delete=<?=$msg['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this message?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'layout/footer.php'; ?>
