## This script adds a couple new entries into the CustomText table.
##
##  Created by Leane Verhulst on March 15, 2022
##

INSERT INTO `CustomText` (`page`, `tag`, `textcontents`) VALUES 
('Participant View', 'post_con', '<p>Thank you for your participation in the convention. With your help, it was a great con. We look forward to your participation in future conventions.</p>'),
('Participant View', 'alerts', '<div class=\"alert alert-primary\" role=\"alert\"><p>The convention does not tolerate harassment in any form. We are dedicated to providing a welcoming, enjoyable, harassment-free convention experience for all individuals, regardless of gender identity and expression, sexual orientation, disability, race, ethnicity, physical appearance, body size, age, origin, or religion. We do not tolerate racism in any form. Convention participants violating these rules may be sanctioned or expelled from the convention without a refund at the discretion of the convention organizers.</p></div>');


INSERT INTO PatchLog (patchname) VALUES ('75ZED_add_custom_text.sql');
