<?php
/**
 * Footer HTML Component
 * Includes copyright and branding information
 * 
 * Author: Elyse Joyeux
 * Version: 1.0.0
 */
?>
<footer class="sms-footer" style="position: fixed; bottom: 0; left: 0; right: 0; padding: 20px 30px; border-top: 1px solid #e0e0e0; text-align: center; color: #fff; font-size: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); z-index: 50; box-shadow: 0 -2px 8px rgba(0,0,0,0.1);">
    <p>
        <strong>Student Management System (SMS)</strong><br>
        © 2026 Elyse Joyeux. All rights reserved.<br>
        <small>Developed by: Elyse Joyeux | Version 1.0.0</small>
    </p>
</footer>
<style>
    /* For pages with sidebars (admin_dashboard, student_dashboard) */
    body:has(.sidebar) .sms-footer {
        left: 260px;
    }
    
    /* For pages without sidebars, center with max-width */
    body:not(:has(.sidebar)) .sms-footer {
        left: 50%;
        transform: translateX(-50%);
        max-width: 600px;
        width: auto;
    }
    
    /* Mobile responsive - full width on smaller screens */
    @media (max-width: 768px) {
        body:has(.sidebar) .sms-footer {
            left: 0;
        }
    }
</style>
