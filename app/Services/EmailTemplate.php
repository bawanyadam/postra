<?php

namespace App\Services;

class EmailTemplate
{
    /**
     * Build a simple, clean submission email.
     * Returns array: [subject, html, text]
     */
    public static function buildSubmissionEmail(string $formName, array $payload, array $meta): array
    {
        $subject = 'Submission: ' . $formName;

        // HTML (Fields first, then Meta) — force light background
        $html  = '<div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;';
        $html .= 'background:#ffffff !important;color:#111 !important;padding:24px;">';
        $html .= '<div style="max-width:680px;margin:0 auto;background:#ffffff !important;border:1px solid #eaeaea;border-radius:8px;';
        $html .= 'padding:24px;color:#111 !important;">';
        $html .= '<h1 style="margin:0 0 12px;font-size:20px;line-height:1.2;color:#111 !important;">' . htmlspecialchars($subject) . '</h1>';
        $html .= '<p style="margin:0 0 16px;color:#555">You received a new website form submission.</p>';

        // Fields table
        $html .= '<h2 style="font-size:13px;margin:18px 0 8px;color:#666;text-transform:uppercase;letter-spacing:.02em;">Fields</h2>';
        $html .= '<table role="presentation" cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;border:1px solid #eaeaea;">';
        foreach ($payload as $k => $v) {
            $val = is_array($v) ? implode(', ', array_map('strval', $v)) : (string)$v;
            $html .= '<tr>';
            $html .= '<td style="background:#fafafa;border:1px solid #eaeaea;border-bottom:0;font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;color:#555;">' . htmlspecialchars((string)$k) . '</td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td style="border:1px solid #eaeaea;border-top:0;">' . htmlspecialchars($val) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        // Footer logo
        $html .= '<div style="text-align:center;margin-top:20px;">';
        $html .= '<img src="https://postra.to/images/logo.png" width="96" alt="Postra" style="display:inline-block;border:0;outline:none;text-decoration:none;">';
        $html .= '</div>';

        $html .= '</div></div>';

        // Plain text (Fields first)
        $text = $subject . "\n\n";
        $text .= "Fields:\n";
        foreach ($payload as $k => $v) {
            $val = is_array($v) ? implode(', ', array_map('strval', $v)) : (string)$v;
            $text .= $k . ': ' . $val . "\n";
        }
        return [$subject, $html, $text];
    }
}
