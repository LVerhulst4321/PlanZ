## This script adds new tables.
##
## Created by BC Holmes
##

create table room_availability_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name varchar(255) NOT NULL
);

create table room_availability_slot (
    id INT AUTO_INCREMENT PRIMARY KEY,
    availability_schedule_id INT NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    divisionid INT
);

create table room_to_availability (
    roomid INT NOT NULL,
    availability_id INT NOT NULL,
    day INT NOT NULL
);


alter table Rooms add column is_online char(1) NOT NULL DEFAULT 'N';
alter table Rooms add column parent_room INT;


INSERT INTO PatchLog (patchname) VALUES ('66WIS_scheduling_time_slots.sql');