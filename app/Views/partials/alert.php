<?php
// app/Views/partials/alert.php
$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);

if ($feedback):
    $type = $feedback['type'] === 'success' ? 'success' : 'error';
    $icon = $feedback['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
?>
<div class="alert alert-<?= $type ?>" id="system-alert">
    <i class="fa <?= $icon ?>"></i>
    <span><?= htmlspecialchars($feedback['message']) ?></span>
    <button type="button" class="close-alert" onclick="this.parentElement.remove()">&times;</button>
</div>

<style>
.alert {
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideInDown 0.4s ease-out;
    position: relative;
    font-weight: 500;
    z-index: 100;
}
.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}
.alert-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}
.close-alert {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: inherit;
    opacity: 0.6;
    padding: 0;
    line-height: 1;
}
.close-alert:hover { opacity: 1; }

@keyframes slideInDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
<?php endif; ?>
