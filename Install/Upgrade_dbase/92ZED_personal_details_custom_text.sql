## This script adds a  CustomText entry for the Personal Details page.
##
##  Created by James Shields on 2023-02-01
##

INSERT INTO `CustomText` (`page`, `tag`, `textcontents`) VALUES 
('Personal Details', 'personal_details_intro', '<p>We are committed to diverse panelist representation on our program items. To help us do that, please consider filling in the following OPTIONAL items of demographic information. All answers will be kept strictly confidential.</p>');


INSERT INTO PatchLog (patchname) VALUES ('92ZED_personal_details_custom_text.sql');
