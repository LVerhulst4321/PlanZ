## This script updates a field.
##
##  Created by Leane Verhulst on August 26, 2021
##


## Update field
ALTER TABLE `Services` ALTER `servicetypeid` SET DEFAULT 1;



INSERT INTO PatchLog (patchname) VALUES ('73ZED_update_services.sql');
