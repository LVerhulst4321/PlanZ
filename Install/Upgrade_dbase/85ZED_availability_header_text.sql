## 
## Move fixed header text referencing Chicago on My Availability page into CustomText.
##
## Created by James Shields
##

INSERT INTO CustomText (page, tag, textcontents) VALUES ('My Availability', 'note_above_times', 'Living on a round planet is really annoying for a virtual convention. Please enter your availability using Chicago time, which is Central Standard Time (GMT-6). We know this can be hard to wrap your brain around. <a href="www.worldtimebuddy.com">www.worldtimebuddy.com</a> can help.');

INSERT INTO PatchLog (patchname) VALUES ('85ZED_availability_header_text.sql');

