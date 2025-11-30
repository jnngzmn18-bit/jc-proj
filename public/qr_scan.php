<?php
require_once '../includes/init.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ' . APP_URL . '/index.php?page=login');
    exit;
}

$user = user();
$page_title = "QR Code Scanner";

// Handle QR scan submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qr_data = trim($_POST['qr_data']);

    // Parse the QR data - should be in format: APP_URL/index.php?page=view_lesson&id=123
    $parsed_url = parse_url($qr_data);
    if ($parsed_url && isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $params);

        if (isset($params['page']) && $params['page'] === 'view_lesson' && isset($params['id'])) {
            $lesson_id = (int)$params['id'];

            // Verify lesson exists and is published
            $stmt = $pdo->prepare('SELECT id, title FROM lessons WHERE id = ? AND status = "published"');
            $stmt->execute([$lesson_id]);
            $lesson = $stmt->fetch();

            if ($lesson) {
                error_log("QR Scan: Found lesson ID $lesson_id for user {$user['id']}");
                // Check if already unlocked
                $stmt = $pdo->prepare('SELECT id FROM lesson_unlocks WHERE lesson_id = ? AND student_id = ?');
                $stmt->execute([$lesson_id, $user['id']]);
                $existing_unlock = $stmt->fetch();

                if (!$existing_unlock) {
                    error_log("QR Scan: Lesson not unlocked yet, attempting to insert");
                    // Unlock the lesson
                    try {
                        $stmt = $pdo->prepare('
                            INSERT INTO lesson_unlocks (lesson_id, student_id, unlock_method, unlocked_at)
                            VALUES (?, ?, ?, NOW())
                        ');
                        $result = $stmt->execute([$lesson_id, $user['id'], 'qr_scan']);
                        error_log("QR Scan: Insert result: " . ($result ? 'success' : 'failed'));

                        // Redirect directly to the lesson after unlocking
                        header('Location: index.php?page=view_lesson&id=' . $lesson_id);
                        exit;
                    } catch (Exception $e) {
                        error_log("Unlock insert error: " . $e->getMessage());
                        $error_message = "Failed to unlock lesson. Please try again.";
                    }
                } else {
                    error_log("QR Scan: Lesson already unlocked");
                    // Already unlocked, redirect to lesson
                    header('Location: ' . APP_URL . '/index.php?page=view_lesson&id=' . $lesson_id);
                    exit;
                }
            } else {
                error_log("QR Scan: Invalid lesson QR code for ID $lesson_id");
                $error_message = "Invalid lesson QR code.";
            }
        } else {
            $error_message = "Invalid QR code format.";
        }
    } else {
        $error_message = "Invalid QR code.";
    }
}

include '../templates/common/header.php';
?>

<div class="container" style="max-width: 800px; margin: 0 auto; padding: var(--space-6);">
    <div class="box">
        <h1 style="text-align: center; margin-bottom: var(--space-6); color: var(--primary-green);">
            📱 QR Code Scanner
        </h1>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" style="margin-bottom: var(--space-6);">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="card" style="text-align: center;">
            <h3 style="margin-bottom: var(--space-4);">Scan QR Code to Unlock Lesson</h3>

            <div style="margin-bottom: var(--space-6);">
                <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto; border: 2px solid var(--gray-300); border-radius: var(--radius-lg); overflow: hidden;"></div>
            </div>

            <div style="margin-bottom: var(--space-4);">
                <p style="color: var(--gray-600); margin-bottom: var(--space-4);">
                    Point your camera at a lesson QR code to unlock it
                </p>
            </div>

            <!-- Manual QR code input (fallback) -->
            <div class="box" style="background: var(--gray-50); border: 1px solid var(--gray-200);">
                <h4 style="margin-bottom: var(--space-3);">Manual Entry (Alternative)</h4>
                <form method="POST" action="">
                    <div style="margin-bottom: var(--space-4);">
                        <label for="qr_data" style="display: block; margin-bottom: var(--space-2); font-weight: 500;">
                            Paste QR Code URL:
                        </label>
                        <input type="url" id="qr_data" name="qr_data" required
                               placeholder="https://example.com/index.php?page=view_lesson&id=123"
                               style="width: 100%; padding: var(--space-3); border: 1px solid var(--gray-300); border-radius: var(--radius-md);">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-qrcode"></i>
                        Unlock Lesson
                    </button>
                </form>
            </div>

            <div style="margin-top: var(--space-6);">
                <a href="index.php?page=student_dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Scanner Script -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrReader = document.getElementById('qr-reader');

    // Check if camera is available
    Html5Qrcode.getCameras().then(cameras => {
        if (cameras && cameras.length) {
            const html5QrCode = new Html5Qrcode("qr-reader");

            html5QrCode.start(
                cameras[0].id, // Use first available camera
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                qrCodeMessage => {
                    // Stop scanning on successful scan
                    html5QrCode.stop().then(() => {
                        // Submit the scanned data
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '';

                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'qr_data';
                        input.value = qrCodeMessage;

                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    });
                },
                errorMessage => {
                    // Ignore errors during scanning
                    console.log("QR scan error:", errorMessage);
                }
            ).catch(err => {
                console.error("Failed to start QR scanner:", err);
                qrReader.innerHTML = '<div style="padding: var(--space-4); color: var(--error); text-align: center;"><i class="fas fa-exclamation-triangle"></i><br>Camera access failed. Please use manual entry below.</div>';
            });
        } else {
            qrReader.innerHTML = '<div style="padding: var(--space-4); color: var(--warning); text-align: center;"><i class="fas fa-camera"></i><br>No camera detected. Please use manual entry below.</div>';
        }
    }).catch(err => {
        console.error("Error getting cameras:", err);
        qrReader.innerHTML = '<div style="padding: var(--space-4); color: var(--error); text-align: center;"><i class="fas fa-exclamation-triangle"></i><br>Camera access not supported. Please use manual entry below.</div>';
    });
});
</script>

<style>
/* QR Scanner specific styles */
#qr-reader {
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gray-100);
}

#qr-reader video {
    width: 100%;
    height: auto;
    border-radius: var(--radius-md);
}

/* Alert styles */
.alert {
    padding: var(--space-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.alert-success {
    background: var(--success-light);
    border: 1px solid var(--success);
    color: var(--success-dark);
}

.alert-error {
    background: var(--error-light);
    border: 1px solid var(--error);
    color: var(--error-dark);
}

.alert i {
    font-size: 1.25rem;
}
</style>

<?php include '../templates/common/footer.php'; ?>
