<?php

function getOrCreateProvider($conn, $name, $label) {
    try {
        $stmt = $conn->prepare("SELECT id FROM video_providers WHERE name = ?");
        $stmt->execute([$name]);
        if($row = $stmt->fetch()) {
            return $row['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO video_providers (name, label, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$name, $label]);
            return $conn->lastInsertId();
        }
    } catch(PDOException $e) {
        return 0;
    }
}

function checkUrlStatus($url) {
    if (!$url) return 0;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

function getStatusColor($code) {
    if ($code >= 200 && $code < 300) return 'success';
    if ($code >= 300 && $code < 400) return 'info';
    if ($code >= 400 && $code < 500) return 'warning';
    if ($code >= 500) return 'danger';
    return 'secondary';
}
?>
