<?php
// Security headers for HTML responses
// Include this file at the top of HTML-generating PHP files

// Prevent clickjacking
header('X-Frame-Options: DENY');

// Prevent MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Enable XSS protection (legacy browsers)
header('X-XSS-Protection: 1; mode=block');

// Referrer policy
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
$cspDirectives = [
    "default-src 'self'",
    "script-src 'self' 'unsafe-inline' https://unpkg.com",  // unpkg.com for fflate
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data:",
    "font-src 'self'",
    "connect-src 'self'",
    "frame-ancestors 'none'",
    "base-uri 'self'",
    "form-action 'self'"
];

header('Content-Security-Policy: ' . implode('; ', $cspDirectives));
