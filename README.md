# PlanZ (aka Zambia)

![PlanZ Logo](./webpages/images/Plan-Z-Logo-192.png)

Convention scheduling tool originally developed for Arisia. Now used by several other conventions.
PlanZ tracks sessions (events, panels, and anything that needs to be scheduled),
participants, and rooms.
PlanZ was originally Zambia, but many changes have been made away from the original code and so a
new name was desired.

## Features
* Track sessions, rooms, and participants
* Comprehensive conflict checking
* Participants log in to enter availability, provide biography, etc.
* Reports for various departments such as technical services and hotel liaison

## Requirements
* PHP 7.1 or greater (Tested on 7.2)
  * XSLT library
  * Multibyte library (mbstring)
* Apache (Should be able to run on other web servers than can handle PHP and MySQL, but not tested)
* MySQL (Tested on 5.6 & 8.0)
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

Check out the [wiki](https://github.com/olszowka/Zambia/wiki) to review the original version of this code.

