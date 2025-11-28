🚀 API ESB Gateway (Wrapper)

Open Source API Management & Load Balancing Tool



📖 Introduction

API ESB Gateway (Wrapper) is a powerful open-source API Gateway and load balancer written in PHP.It is designed for high-performance, in-memory execution and supports message queuing via Redis.

✨ Key Features:

⚡ Preprocess user requests before sending to backend API servers

🛡️ Rate limiting control

🔄 Manipulate backend API responses before sending to clients

📡 Transparent request forwarding (no manipulation if desired)

🧩 Rule-based request/response processing

🚀 Optimized for high traffic loads

🏗️ Architecture

Component

Description

Config.php

Stores load balancing strategy, timeout, health check intervals, Redis connection info, and backend health check endpoint

MessagesProcessingRules.php

Defines rules for request preprocessing and response manipulation

MainWorker.php

Entry point for all requests; stores messages in Redis if no backend API is alive

QueueSenderWorker.php

Daemon/cronjob that processes stored messages via backend APIs using load balancing strategy

HealthCheckWorker.php

Monitors backend API availability via health check endpoint and updates Redis

🛠 All project files are written in PHP.

📦 Requirements

Web server: Apache2 (recommended) or Nginx

PHP 8.x

php8.x-redis extension

mod_rewrite enabled

⚙️ Installation & Setup

Copy all files into a directory, e.g. /opt/APIGateWay

Configure Apache Virtual Host or Alias:

Alias /API /opt/APIGateWay
<Directory /opt/APIGateWay>
    Options +FollowSymLinks
    AllowOverride All
    Require all granted

    DirectoryIndex MainWorker.php

    RewriteEngine On
    RewriteBase /API/

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ MainWorker.php [L]
</Directory>

Enable mod_rewrite and restart Apache:

sudo a2enmod rewrite
sudo systemctl restart apache2

📚 Documentation

Works under Apache2 or Nginx

Apache is recommended for easier configuration

Supports virtual host or Alias integration

📜 License

Released under the terms of the Apache License Version 2.0.See the LICENSE file for details.

🌟 Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you’d like to change.

🙌 Acknowledgements

Built with ❤️ using PHP and Redis

Designed for scalability, reliability, and operational transparency
