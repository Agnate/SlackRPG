#Setup

Clone this repo into your project folder. For permissions, `public` is the only folder that needs to be accessed from the outside, so it's best to point your webroot there.

Run `composer install` at the root of this project to install all the required components.

Create the `config.php` file (using the `config.example.php`) and fill it out with your credentials.


###Database

Create a new database and user and place those credentials in the `config.php` file. The database is generated entirely from code, and can be executed on the command line:

- `cd /your/project/folder`
- `php bin/update.php -v 0.0.0` &rarr; Select the newest version of data (you can look in the `bin/updates` folder to see the newest).


#Running the Server

You can run the server temporarily from the commandline:

- `cd /your/project/folder`
- `php bin/server.php`

To have it run continuously, I would advise installing Supervisor or something simliar.

Supervisor: https://www.digitalocean.com/community/tutorials/how-to-install-and-manage-supervisor-on-ubuntu-and-debian-vps

- `vim /etc/supervisor/conf.d/rpgbetaserver.conf`
- Paste the following:

```
[program:slack_rpg_test_server]
command=/usr/bin/php bin/server.php
directory=/rpg_slack/test
autostart=false
autorestart=false
stderr_logfile=/rpg_slack/test/log/server.err.log
stdout_logfile=/rpg_slack/test/log/server.out.log
```