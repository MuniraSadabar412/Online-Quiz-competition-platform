<?php
/**
 * Shared Helper Functions & Formatting Utilities
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

/**
 * XSS prevention shortcut
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format user display name gracefully if email was entered as name
 */
function format_display_name(?string $name): string {
    if (empty($name)) return 'User';
    if (strpos($name, '@') !== false) {
        $prefix = explode('@', $name)[0];
        return ucwords(str_replace(['.', '_', '-'], ' ', $prefix));
    }
    return $name;
}

/**
 * Generate absolute URL relative to application root (e.g. /quiz/...)
 */
function url(string $path = ''): string {
    $root = get_project_root();
    $path = ltrim($path, '/');
    return empty($path) ? ($root ?: '/') : ($root . '/' . $path);
}

/**
 * Set flash alert message for next page view
 */
function set_flash_message(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear all queued flash messages
 */
function get_flash_messages(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages as styled dismissible alerts
 */
function display_flash_messages(): void {
    $messages = get_flash_messages();
    if (empty($messages)) {
        return;
    }
    echo '<div class="flash-messages-container">';
    foreach ($messages as $msg) {
        $type = e($msg['type']);
        $text = e($msg['message']);
        
        $icon = '';
        if ($type === 'success') {
            $icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        } elseif ($type === 'danger') {
            $icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        } elseif ($type === 'warning') {
            $icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
        } else {
            $icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        }

        echo "<div class=\"alert alert-{$type}\" role=\"alert\">";
        echo "<div style=\"display:flex;align-items:center;gap:10px;line-height:1.4;\">{$icon}<span>{$text}</span></div>";
        echo "<button type=\"button\" class=\"alert-close\" onclick=\"this.parentElement.remove();\" aria-label=\"Dismiss\">&times;</button>";
        echo "</div>";
    }
    echo '</div>';
}

/**
 * Format elapsed time in human-readable MM:SS or HH:MM:SS
 */
function format_duration(int $seconds): string {
    if ($seconds < 0) $seconds = 0;
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    if ($minutes >= 60) {
        $hours = floor($minutes / 60);
        $remMin = $minutes % 60;
        return sprintf('%dh %02dm %02ds', $hours, $remMin, $secs);
    }
    return sprintf('%02dm %02ds', $minutes, $secs);
}

/**
 * Format datetime into friendly string
 */
function format_date(?string $datetime, string $format = 'M d, Y h:i A'): string {
    if (empty($datetime)) {
        return '—';
    }
    $ts = strtotime($datetime);
    if (!$ts) {
        return '—';
    }
    return date($format, $ts);
}

/**
 * Render difficulty badge HTML
 */
function get_difficulty_badge(string $difficulty): string {
    $diff = strtolower($difficulty);
    switch ($diff) {
        case 'easy':
            return '<span class="badge badge-difficulty badge-easy">Easy</span>';
        case 'hard':
            return '<span class="badge badge-difficulty badge-hard">Hard</span>';
        case 'medium':
        default:
            return '<span class="badge badge-difficulty badge-medium">Medium</span>';
    }
}

/**
 * Render status badge HTML
 */
function get_status_badge(string $status): string {
    $stat = strtolower($status);
    switch ($stat) {
        case 'published':
            return '<span class="badge badge-status badge-success">Published</span>';
        case 'inactive':
            return '<span class="badge badge-status badge-danger">Inactive</span>';
        case 'draft':
            return '<span class="badge badge-status badge-warning">Draft</span>';
        case 'completed':
            return '<span class="badge badge-status badge-success">Completed</span>';
        case 'in_progress':
            return '<span class="badge badge-status badge-info">In Progress</span>';
        case 'expired':
            return '<span class="badge badge-status badge-danger">Expired</span>';
        default:
            return '<span class="badge badge-status badge-secondary">' . e(ucfirst($stat)) . '</span>';
    }
}

/**
 * Get encouraging performance feedback message based on percentage
 */
function get_performance_feedback(float $percentage): array {
    if ($percentage >= 90) {
        return [
            'title' => 'Outstanding Performance!',
            'badge' => 'Mastery',
            'class' => 'perf-gold',
            'desc' => 'Exceptional mastery! You ranked among top-tier academic performers with virtually flawless execution.'
        ];
    } elseif ($percentage >= 75) {
        return [
            'title' => 'Commendable Effort!',
            'badge' => 'Proficient',
            'class' => 'perf-silver',
            'desc' => 'Great work! You demonstrated solid comprehension and strong analytical reasoning.'
        ];
    } elseif ($percentage >= 50) {
        return [
            'title' => 'Good Attempt!',
            'badge' => 'Competent',
            'class' => 'perf-bronze',
            'desc' => 'You cleared the passing benchmark. Reviewing missed questions will further strengthen your proficiency.'
        ];
    } else {
        return [
            'title' => 'Needs Further Revision',
            'badge' => 'Apprentice',
            'class' => 'perf-review',
            'desc' => 'Do not be discouraged! Solidify your foundational concepts and re-attempt subsequent competitive challenges.'
        ];
    }
}
