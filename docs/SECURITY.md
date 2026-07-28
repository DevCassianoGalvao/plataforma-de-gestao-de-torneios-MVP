# Security

Server-side CSRF, prepared PDO statements, scoped authorization, private ID-based downloads, MIME/size upload validation, session regeneration, HttpOnly/SameSite cookies, rate limiting and security headers are implemented. Keep `APP_DEBUG=false` in production. Review logs after failed authentication, upload, export and download events.

Remaining operational controls: configure WAF/rate limiting at host level, SMTP, external monitoring and periodic penetration testing.
