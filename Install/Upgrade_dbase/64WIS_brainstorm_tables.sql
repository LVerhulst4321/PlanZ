## This script adds tables and fields for new Brainstorm functionality
##
## Created by BC Holmes
##

CREATE TABLE reg_perennial_con_info (
    id int AUTO_INCREMENT PRIMARY KEY,
    name varchar(90) NOT NULL,
    website_url varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE reg_con_info (
    id int AUTO_INCREMENT PRIMARY KEY,
    perennial_con_id int NOT NULL,
    name varchar(90) NOT NULL,
    header_img_name varchar(90),
    active_from_time timestamp NOT NULL DEFAULT NOW(),
    active_to_time timestamp NOT NULL DEFAULT NOW(),
    reg_open_time timestamp NOT NULL DEFAULT NOW(),
    reg_close_time timestamp NOT NULL DEFAULT NOW(),
    con_start_date date NOT NULL,
    con_end_date date NOT NULL,
    FOREIGN KEY (perennial_con_id) REFERENCES reg_perennial_con_info (id) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE con_key_dates (
    id int AUTO_INCREMENT PRIMARY KEY,
    con_id int NOT NULL,
    external_key varchar(255) NOT NULL,
    from_time timestamp NOT NULL DEFAULT NOW(),
    to_time timestamp NOT NULL DEFAULT NOW(),
    CONSTRAINT external_key_uniq UNIQUE (con_id, external_key)
);

CREATE VIEW current_con AS
    SELECT c.id, c.name, p.name AS perennial_name, c.con_start_date, c.con_end_date
      FROM reg_con_info c, reg_perennial_con_info p
    WHERE c.perennial_con_id = p.id
    AND c.active_to_time > now()
    AND c.active_from_time <= now();


ALTER TABLE `Divisions` ADD COLUMN external_key varchar(255);

INSERT INTO PatchLog (patchname) VALUES ('64WIS_brainstorm_tables.sql');