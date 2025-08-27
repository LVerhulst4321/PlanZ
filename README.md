# PlanZ (aka Zambia)

![PlanZ Logo](./webpages/images/Plan-Z-Logo-192.png)

PlanZ is a convention scheduling tool originally developed for Arisia.
PlanZ tracks sessions (events, panels, and anything that needs to be scheduled),
participants, and rooms. PlanZ is a fork of [Zambia](https://github.com/olszowka/Zambia/);
enough changes have been made to the Zambia codebase that we decided a new
name was in order.

## Features
* Track sessions, rooms, and participants
* Comprehensive conflict checking
* Participants log in to enter availability, provide biography, etc.
* Reports for various departments such as technical services and hotel liaison

## Requirements
* PHP 7.4 or greater (mostly tested on 8.1, but there may be some minor issues with 8.X)
  * XSLT library
  * Multibyte library (mbstring)
* Apache or Nginx (Should be able to run on other web servers than can handle PHP and MySQL, but not tested)
* MySQL or MariaDB (Tested on MySQL 5.6 & 8.0, and on MariaDB 10.5)
* SMTP connection for use as mail relay (only if you want to send email from PlanZ)
  * Note: many hosts limit use of their mail relays in ways not compatible with PlanZ
* The participant photo upload option requires the PHP extension GD library

## Built In Dependencies
These libraries are included in the repo and should just work if you leave as is
* Client Side
  * Bootstrap 2.3.2
  * Bootstrap 4.5.0
  * Bootstrap Multiselect 1.1.7
  * Choices 9.0.0
  * Croppie 2.6.5
  * DataTables 1.10.16
  * JQuery 1.7.2
  * JQuery 3.5.1
  * JQueryUI 1.8.16
  * ParseCSV 0.4.3 beta
  * Tabulator 4.9.3
  * TinyMCE 5.6.2
* Server Side
  * Swift mailer 5.4.8
  * Guzzle 6.5.3

## Integrations
Other software which can work with PlanZ:
* Convention Guide scripts (https://github.com/pselkirk/conguide), a tool for producing a printable pocket program in InDesign, including a schedule grid
* KonOpas (https://github.com/eemeli/konopas), a free tool for publishing the schedule to mobile devices
* ConClár (https://github.com/lostcarpark/conclar), an online Program Guide tool for conventions

## More Information

### Installation

Are you trying to install PlanZ? Check out the [installation notes](./Install/INSTALL.md).

A portion of the application is built as a React client. You'll need to [build and deploy](./client/README.md) the React code as well.

### Documentation

We have some [user documentation over here](./Documentation/User_Documentation/README.md).

