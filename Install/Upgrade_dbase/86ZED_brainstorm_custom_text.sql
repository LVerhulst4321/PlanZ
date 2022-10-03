## 
## Make improvements to brainstorm pages, allowing more information to be driven by 
## server configuration.
##
## Created by BC Holmes
##

INSERT INTO CustomText (page, tag, textcontents) VALUES ('Brainstorm', 'sidebar', 'We are humble purveyors of refined con program content and are in search of your very best topics.');

DROP VIEW current_con;

CREATE VIEW current_con AS
    SELECT c.id, c.name, p.name AS perennial_name, c.con_start_date, c.con_end_date, p.website_url
      FROM reg_con_info c, reg_perennial_con_info p
    WHERE c.perennial_con_id = p.id
    AND c.active_to_time > now()
    AND c.active_from_time <= now();

INSERT INTO PatchLog (patchname) VALUES ('86ZED_brainstorm_custom_text.sql');