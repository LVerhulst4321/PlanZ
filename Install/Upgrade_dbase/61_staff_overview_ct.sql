## This script adds custom text fields for the main block of text
## for staffpage and overview on participant side
##
##	Created by Syd Weinstein on March 5, 2021
## 	Copyright (c) 2021 by Peter Olszowka. All rights reserved. See copyright document for more details.
##
INSERT INTO CustomText(page, tag, textcontents)
VALUES ('Participant View', 'part_overview', ''),
('Staff Overview', 'staff_overview', "<p>Please note the tabs above. One of them will take you to your participant view. Another will allow you to manage Sessions. Note that Sessions is the generic term we are using for all Events, Films, Panels, Anime, Video, etc.</p>
<p>The general flow of sessions over time is: </p>
<ul>
<li>Brainstorm - New session idea put in to the system by one of our brainstorm users. The idea may or may not be sane or good. It could be too big or too small or duplicative.</li>
<li>Edit Me - New session idea that a participant or staff member entered. An idea entered by a brainstorm user that is non-offensive should be moved to this status. These are still rough and may well have issues. Still could be duplicates.</li>
<li>Vetted - A real session that we'd like to see happen. At this point the language should be fairly close to final in the description. Spell checking and grammar checking should have happened. It needs have publication status, a type, kid category, division and a room set. Please check the duration (defaults to 1 hour) and the various things the session might need (like power, mirrors, etc.) This is the minimal status that participants are allowed to sign up for. Avoid duplicates (however the list is still approximately 3 times what will actually run)</li>
<li>Assigned - Session has participants assigned to it.</li>
<li>Scheduled - Session is in the schedule (don't set this by hand as the tool actually sets this for you when you schedule it in a room!) The language needs to match what you want to see <strong>published</strong>.</li>
<li>Occurred - It Happened! Fill in the estimated attendance and update any other needed information when moving the session to this status.</li>
</ul>
<p>There are 2 other statuses that a session can have:</p>
<ul>
<li>Dropped - The bit bucket. This idea is no longer under consideration. Nor will it ever be again. The most frequent reason for this is identical data to another session.</li>
<li>Cancelled - Over all a good idea, but it isn't going to happen this year. Maybe it is too close to another idea, maybe it was dependent on a particular person attending, maybe there were scheduling complications. You should probably still say why it was cancelled. This is a category we can mine for ideas in future years.</li>
</ul>
<p>For your reference there are several statuses pulled over from previous years. They are here for your reference and in general should be mined for information. Feel free to import a session from a previous year into the workflow for this year if you want to see it happen this year.</p>");

INSERT INTO PatchLog (patchname) VALUES ('61_staff_overview_ct.sql');
