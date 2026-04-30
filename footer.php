<?php

?>
<footer class="sms-footer">
    <p>
        <strong>Student Management System (SMS)</strong><br>
        © 2026 Elyse Joyeux. All rights reserved.<br>
        <small>Developed by: Elyse Joyeux | Version 1.0.0</small>
    </p>
</footer>
<style>
.sms-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px 30px;
    border-top: 1px solid #282f3b;
    text-align: center;
    color: #fff;
    font-size: 12px;
    background: #171b22;
    z-index: 50;
    box-shadow: 0 -8px 24px rgba(17, 20, 29, 0.12);
    width: 100%;
}

/* For dashboard pages with sidebars - position to the right */
body[data-page-type="dashboard"] .sms-footer,
body.dashboard-page .sms-footer {
    left: 260px;
    right: auto;
    width: auto;
    max-width: calc(100% - 260px);
}

/* For login/register/forgot-password pages - center it */
body[data-page-type="auth"] .sms-footer,
body.auth-page .sms-footer {
    left: 50%;
    transform: translateX(-50%);
    max-width: 600px;
    width: auto;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .sms-footer {
        left: 0;
        right: 0;
        width: 100%;
        transform: none;
    }

    body[data-page-type="dashboard"] .sms-footer,
    body.dashboard-page .sms-footer {
        left: 0;
        right: 0;
        width: 100%;
        max-width: 100%;
    }

    body[data-page-type="auth"] .sms-footer,
    body.auth-page .sms-footer {
        left: 0;
        right: 0;
        width: 100%;
        transform: none;
    }
}
</style>
