#API ESB Gateway (Wrapper)  - Open source API Management Tool
################################

##Introduction
************
API ESB Gateway (Wrapper) is a very complete open source API load balancer and API Gateway (Wrapper):

	* Handle user's requests and preprocess them before sending to backend API servers
	* Based on PHP 
	* Controlling Ratelimit
	* Manipulating API Backend's responce messages and then send to users
	* Gets api requests from you users and sends their pathes without any manipulation to backend API servers.
	* Can process the requests before sending to backend API servers

API ESB Gateway relies on message queuing through Redis Server, it is designed for performance,
high traffic loads and full in-memory execution.



##Architecture
************
**API ESB Gateway (Wrapper)**
	- Config.php
	- MessagesProcessingRules.php
	- MainWorker.php
	- QueueSenderWorker.php
	- HealthCheckWorker.php

All project files has been written in PHP


##Requirements
************
	- A web server such as nginx or apache
	- php 8.x
	- php8.x-redis
	- rewrite module

##Documentation
*************

This App can be run under any web server such as NginX or Apache2
Apache is mostly recommended for easy to configure and use.

Copy all files in a directory for example /opt/APIGateWay

It can be used by virtual host or Alias in current cirtual hosts.

	```
	Alias /API	/opt/APIGateWay
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
	```
	
mod_rewrite module must be enabled 
	```
	sudo a2enmod rewrite
	sudo systemctl restart apache2
	```


##License
*******
API ESB Gateway (Wrapper) is released under the terms of the [Apache License Version 2]. See **`LICENSE`** file for details.
