# API ESB Gateway (Wrapper)  - Open source API Management Tool
################################

## Introduction
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



## Architecture
************
** API ESB Gateway (Wrapper)**
	- Config.php
		* Stores:
			- load balancer backed API server selection strategy
			- timeout and health check intervals
			- redis connection info and keys info
			- health check endpint used on backend API
	- MessagesProcessingRules.php
		* some rules are processed before sending requests to backend API server
		* some rules are processed after sending request to backend and can manipulate the messages comming from them.
	- MainWorker.php
		* all requests must direct to this file
		* if there isn't any alive backend API all messages will be written and stored in redis key.
	- QueueSenderWorker.php
		* this is a seperated app that checks redis to get list of available backend API servers
		* after that will be process stored messages through backend API servers by using of load balancing startegy in config file
		* must be run as a daemon, systemd or cronjob intervally
	- HealthCheckWorker.php
		* this app checks avaailablity of backend API servers by using the endpoint defined on Config.php file.
		* after that saved the status of each vendor to redis.

All project files has been written in PHP


## Requirements
************
	- A web server such as nginx or apache
	- php 8.x
	- php8.x-redis
	- rewrite module

## Documentation
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


## License
*******
API ESB Gateway (Wrapper) is released under the terms of the [Apache License Version 2]. See **`LICENSE`** file for details.
