<?php
session_start();
require_once '../app/config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM requests WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: request_list.php');
    exit;
}

// Handle Status Update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    if (in_array($status, ['pending', 'completed', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE requests SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }
    header('Location: request_list.php');
    exit;
}

// Fetch Requests
$stmt = $conn->query("SELECT * FROM requests ORDER BY created_at DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Request List</h2>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Anime Name</th>
                    <th>Link</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= $req['id'] ?></td>
                    <td><?= htmlspecialchars($req['title']) ?></td>
                    <td>
                        <?php if($req['ref_url']): ?>
                            <a href="<?= htmlspecialchars($req['ref_url']) ?>" target="_blank">Link</a>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($req['message']) ?></td>
                    <td>
                        <span class="badge bg-<?= $req['status'] == 'completed' ? 'success' : ($req['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                            <?= ucfirst($req['status']) ?>
                        </span>
                    </td>
                    <td><?= $req['created_at'] ?></td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Status
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="request_list.php?id=<?= $req['id'] ?>&status=pending">Pending</a></li>
                                <li><a class="dropdown-item" href="request_list.php?id=<?= $req['id'] ?>&status=completed">Completed</a></li>
                                <li><a class="dropdown-item" href="request_list.php?id=<?= $req['id'] ?>&status=rejected">Rejected</a></li>
                            </ul>
                        </div>
                        <a href="request_list.php?delete=<?= $req['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
