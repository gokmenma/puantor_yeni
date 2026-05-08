<?php
require_once ROOT . "/Model/SupportsModel.php";
require_once ROOT . "/Model/SupportsMessagesModel.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Security;

// Dynamic Absolute AJAX URL Calculation
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$ajaxUrl = $protocol . $domainName . ($script_dir == '/' ? '' : $script_dir) . '/api/supports/tickets.php';


$encrypted_id = $_GET['id'] ?? '';
$support_id = Security::decrypt($encrypted_id);

if (!$support_id) {
    echo "<div class='alert alert-danger m-3'>Geçersiz destek talebi ID'si.</div>";
    exit;
}

$supportsModel = new SupportsModel();
$messagesModel = new SupportsMessagesModel();

$support = $supportsModel->find($support_id);
$messages = $messagesModel->getMessagesByTicketId($support_id);

if (!$support) {
    echo "<div class='alert alert-danger m-3'>Destek talebi bulunamadı.</div>";
    exit;
}

$lastMessage = $messagesModel->getLastMessageByTicketId($support_id);
$lastAuthor = $lastMessage->author ?? 0;

// If the last message author is 0, then the user sent it, meaning we are waiting for a support reply
if ($lastAuthor == 0) {
    $showNewMessage = false;
} else {
    $showNewMessage = true;
}
?>

<style>
:root {
    --chat-bg: #f8fafc;
    --bubble-user-bg: #4f46e5;
    --bubble-user-text: #ffffff;
    --bubble-support-bg: #ffffff;
    --bubble-support-text: #1d273b;
    --bubble-support-border: rgba(0, 0, 0, 0.05);
}

body[data-bs-theme="dark"] {
    --chat-bg: #0f172a;
    --bubble-user-bg: #6366f1;
    --bubble-user-text: #ffffff;
    --bubble-support-bg: #1e293b;
    --bubble-support-text: #f4f6fa;
    --bubble-support-border: rgba(255, 255, 255, 0.1);
}

.chat-container {
    background-color: var(--chat-bg);
    border-radius: 20px;
    padding: 1rem;
    min-height: 400px;
    display: flex;
    flex-direction: column;
}

.chat-bubble-wrapper {
    display: flex;
    margin-bottom: 1rem;
    max-width: 85%;
}

.chat-bubble-wrapper.user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.chat-bubble-wrapper.support {
    align-self: flex-start;
}

.chat-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 4px;
}

.chat-bubble-wrapper.user .chat-avatar {
    margin-left: 8px;
    background-color: rgba(79, 70, 229, 0.15);
    color: #4f46e5;
}

body[data-bs-theme="dark"] .chat-bubble-wrapper.user .chat-avatar {
    background-color: rgba(99, 102, 241, 0.2);
    color: #818cf8;
}

.chat-bubble-wrapper.support .chat-avatar {
    margin-right: 8px;
    background-color: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.chat-bubble {
    border-radius: 16px;
    padding: 0.8rem 1rem;
    font-size: 0.85rem;
    line-height: 1.4;
    position: relative;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.chat-bubble-wrapper.user .chat-bubble {
    background-color: var(--bubble-user-bg);
    color: var(--bubble-user-text);
    border-bottom-right-radius: 4px;
}

.chat-bubble-wrapper.support .chat-bubble {
    background-color: var(--bubble-support-bg);
    color: var(--bubble-support-text);
    border: 1px solid var(--bubble-support-border);
    border-bottom-left-radius: 4px;
}

.chat-meta {
    font-size: 0.65rem;
    margin-top: 4px;
    display: block;
    opacity: 0.7;
}

.chat-bubble-wrapper.user .chat-meta {
    text-align: right;
}

.input-bar {
    background: var(--support-card-bg);
    border: 1px solid var(--support-card-border);
    border-radius: 16px;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.input-bar textarea {
    border: none !important;
    background: transparent !important;
    resize: none;
    font-size: 0.85rem;
    max-height: 80px;
    outline: none !important;
    box-shadow: none !important;
}

.input-bar textarea:focus {
    outline: none !important;
    box-shadow: none !important;
}
</style>

<div class="container px-0">
    <div class="mb-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="tickets" class="btn btn-icon btn-sm btn-outline-secondary border-0 text-muted">
                <i class="ti ti-chevron-left" style="font-size: 1.5rem;"></i>
            </a>
            <div>
                <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($support->subject); ?></h2>
                <p class="text-muted text-xs mb-0">Destek Bildirimi #<?php echo $support_id; ?></p>
            </div>
        </div>
        <div class="d-flex gap-1">
            <?php if ($support->status == 0): ?>
                <button type="button" class="btn btn-sm btn-outline-danger px-3" id="closeTicketBtn" onclick="closeTicket()" style="border-radius: 8px;">
                    <i class="ti ti-lock me-1"></i> Kapat
                </button>
            <?php else: ?>
                <span class="badge bg-secondary-lt text-xs" style="padding: 6px 12px; border-radius: 8px;">Kapalı</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Log Area -->
    <div class="chat-container mb-4">
        <div class="d-flex flex-column gap-1" id="chat-feed" style="overflow-y: auto; max-height: 50vh;">
            
            <?php if (empty($messages)): ?>
                <div class="text-center py-4 text-muted text-xs">Mesaj bulunamadı.</div>
            <?php else: ?>
                <?php 
                // Display in chronological order
                $chrono_messages = array_reverse($messages);
                foreach ($chrono_messages as $message): 
                    $is_user = ($message->author == 0);
                    $author_name = $is_user ? 'Ben' : 'Destek';
                    $avatar_text = $is_user ? mb_substr($_SESSION['user']->full_name ?? 'U', 0, 1, 'UTF-8') : 'D';
                    $msg_date = new DateTime($message->created_at);
                    $formatted_time = $msg_date->format('H:i') . ' • ' . $msg_date->format('d.m.Y');
                ?>
                    <div class="chat-bubble-wrapper <?php echo $is_user ? 'user' : 'support'; ?>">
                        <div class="chat-avatar">
                            <?php if ($is_user): ?>
                                <?php echo htmlspecialchars($avatar_text); ?>
                            <?php else: ?>
                                <i class="ti ti-headset"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="chat-bubble">
                                <?php echo preg_replace('/\?/', '', strip_tags($message->message)); ?>
                            </div>
                            <span class="chat-meta text-muted">
                                <?php echo $formatted_time; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <!-- Reply input bar -->
    <?php if ($support->status == 0): ?>
        <?php if ($showNewMessage): ?>
            <form id="replyForm" onsubmit="sendReply(event)">
                <input type="hidden" name="action" value="newTicketMessage">
                <input type="hidden" name="support_id" value="<?php echo $encrypted_id; ?>">
                <div class="input-bar">
                    <textarea class="form-control" name="message" required rows="1" placeholder="Mesajınızı yazın..." oninput="autoGrow(this)"></textarea>
                    <button type="submit" class="btn btn-icon btn-primary rounded-circle" id="sendReplyBtn" style="width: 40px; height: 40px; background: #4f46e5; border: none; flex-shrink: 0;">
                        <i class="ti ti-send" style="font-size: 1.1rem;"></i>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center p-3 rounded-3 bg-light border text-xs text-danger blinking-text" style="border-radius: 12px !important; font-weight: 500;">
                <i class="ti ti-clock-play me-1"></i> Destek ekibinin cevabı bekleniyor.
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center p-3 rounded-3 bg-light border text-xs text-secondary" style="border-radius: 12px !important; font-weight: 500;">
            <i class="ti ti-lock me-1"></i> Bu destek bildirimi kapatılmıştır. Yeni bir destek bildirimi açabilirsiniz!
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Auto scroll to bottom
    const feed = document.getElementById('chat-feed');
    if (feed) {
        feed.scrollTop = feed.scrollHeight;
    }
});

function autoGrow(element) {
    element.style.height = "5px";
    element.style.height = (element.scrollHeight) + "px";
}

function sendReply(e) {
    e.preventDefault();
    const btn = $('#sendReplyBtn');
    const originalContent = btn.html();
    
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');
    
    const formData = new FormData(e.target);
    
    $.ajax({
        url: '<?php echo $ajaxUrl; ?>',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false).html(originalContent);
            if (response.status === 'success') {
                location.reload();
            } else {
                Swal.fire('Hata', response.message || 'Mesaj gönderilemedi.', 'error');
            }
        },
        error: function() {
            btn.prop('disabled', false).html(originalContent);
            Swal.fire('Hata', 'Bir ağ hatası oluştu.', 'error');
        }
    });
}

function closeTicket() {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu destek bildirimini kapatmak istediğinize emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        confirmButtonText: 'Evet, Kapat',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?php echo $ajaxUrl; ?>',
                method: 'POST',
                data: {
                    action: 'closeTicket',
                    id: '<?php echo $encrypted_id; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        Swal.fire('Hata', response.message || 'İşlem gerçekleştirilemedi.', 'error');
                    }
                }
            });
        }
    });
}
</script>
