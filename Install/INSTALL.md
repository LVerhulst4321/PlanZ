# Installing PlanZ on your webserver

## Comments:
  - This document is still pretty rough.
  - You really want to read this whole file before doing anything.
  - If you want to help revise this document, speak up!

## 0 - Prep work

You need a server with:
  - apache or nginx
     - PlanZ might work with other web servers, but hasn't been tested on them.
  - php
     - version 7.X >= 7.1 or any version of 8.X
     - needs to have xsl and multibyte libraries installed and enabled
       (often, but not always, included by default)
     - php-fpm is needed if you are hosting on Nginx. It is optional on
       Apache, but will generally allow for better performance.
  - mysql or mariadb
     - a user with full privileges including privileges to create TRIGGERS
       (during install) and run them (during regular usage)
  - a mail relay to accept mail for sending via smtp
       (optional; needed only if you want to send email from PlanZ)
Please test them and make sure they work.

You need to decide on:
  - database name          (planzdemo is used here in)
  - database user name     (planzdemo is used here in)
  - database user password (4fandom is used here in)
  - web install location   (/var/www/public_html/planzdemo is used here in)

## 1 -  Create a database and a user

You need to have root access to the mysql instance to do this.
If you don't please ask the person who does to do these 4 steps.

    mysql -u root -p

    mysql> create database planzdemo;
    Query OK, 1 row affected (0.00 sec)

    mysql> grant all on planzdemo.* to 'planzdemo'@'localhost'
           identified by '4fandom';
    Query OK, 0 rows affected (0.07 sec)

    mysql> grant lock tables on planzdemo.* to 'planzdemo'@'localhost' ;
    Query OK, 0 rows affected (0.00 sec)

    mysql> flush privileges;
    Query OK, 0 rows affected (0.31 sec)

Press `Ctrl+D` or type `exit` to quit mysql.

You may want to create multiple MySQL users with each having only the necessary privileges, e.g.
  - administrator — Everything as shown above
  - php user — SELECT, INSERT, UPDATE, DELETE, and LOCK TABLES
  - backup user — SELECT and LOCK TABLES

## 2 -  Setting up the database

You'll need the account and the database created in step 1.
You can install the empty database or you can install the sample database or you can
create a copy and modify for your own event.

    mysql -u planzdemo -p

    mysql> use planzdemo
    Database changed

    mysql> \. EmptyDbase.dump
    Query OK ...   (snipped for sanity)

PlanZ is in active development, and the empty database is not always up to date with the
latest changes. If a change needs to change the database, it will add a SQL script to the
`Install/Upgrade_dbase` directory. These can be applied manually, but we have provided a
PHP script that can be run from the command line to check for any new database updates and
apply them. After you have created your database, you should run the following script
from the base directory (the one containing the `webpages` directory):

    php scripts/db_update.php

If you want to keep up to date with PlanZ changes, you should run the above script
every time you update the PlanZ code.

Now you have an empty database that is ready for use with PlanZ.  You may instead
use DemoDbase.dump or SampleDbase.dump.
  - EmptyDbase.dump — only tables required for PlanZ to run at all or with hard coded
        values are populated.
  - SampleDbase.dump — contains data from EmptyDbase.dump plus some configuration data
        you will need to edit.  Also contains users “1” and “2” with password “demo”.
  - DemoDbase.dump — contains data from EmptyDbase.dump, SampleDbase.dump, and some
        participant preference and schedule data.

## 3 - Setting up the webpages

Checking out the html and php code.

    cd /var/www/public_html
    git clone https://github.com/leaneverhulst/PlanZed.git planzdemo

## 4 - Tweak the configuration to use your database and specify other preferences

You want to copy config/db_name_sample.php to config/db_name.php
and edit it as needed.  In other words, `db_name.php` should be in the config
directory of PlanZ.  The file `db_functions.php` loads this file, so you may edit the
location if necessary.
Then copy `config/db_env_sample.php` to a location outside of the webroot and
have your system administrator set up the variables required in that file. Then
rename the file to db_env.php and update the location in the db_name.php file.

## 5 -  Check it all out

http://planzdemo.yourwebhost.com/

or whatever your URL is...

## 6 - Adding an account for PlanZ so you can log in.

PlanZ can take a feed from a registration system to create and configure users.  However, the
code for handling that is specific to each registration system and not in the master branch.

There is a script `add_planz_users.php` in scripts directory to add users.

Usage:

    php -f add_planz_users.php input_file.csv

The input_file should have field names in first row.  See `add_planz_users_sample_input.csv`.  If there are one or more
columns in CongoDump you don't care about, you may skip them entirely including skipping them from the header row. The
other fields are all required.

## 7 - Mail relay configuration

If you want to have PlanZ send email for password resets for mailmerges to various groups of users, you
need to arrange for a mail relay serving and configure PlanZ to connect to it.  Consult the documentation for your
mail relay service and configure the following constants in db_env.php:

    SMTP_ADDRESS
    SMTP_PORT
    SMTP_PROTOCOL
    SMTP_USER
    SMTP_PASSWORD

- You may leave SMTP_USER and/or SMTP_PASSWORD blank to skip authentication if that is appropriate for your mail relay.
- Likely options for SMTP_PORT are "587", "2525", "25", or "465".
- Options for SMTP_PROTOCOL are "", "SSL", or "TLS".  Blank/Default is no encryption.

## 8 - Build and Deploy the React Application

Follow the [build instructions](../client/README.md) for building the React application. The resulting `dist/` directory
needs to be deployed to your server.

## 9 - Backups are a good thing

If you are changing php and html files, I suggest you fork PlanZ on github and commit your changes to your fork.

If you care about dbase content, see `backup_mysql` and `clean_backups` in the
scripts directory.  You'll want to run them or something similar.

