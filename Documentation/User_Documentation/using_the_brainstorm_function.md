# Submitting Session Ideas using Brainstorm

Many cons like to solicit session ideas from their members: past attendees often have good ideas for future panels, readings or are interested in running a gaming session or hosting a party. PlanZ provides a tool -- the brainstorming tool -- to enable this.

While the brainstorming tool is fairly straight-forward for users looking to propose sessions, it requires a bit of data set-up that has not yet been made easy for admins.

## Some Design Decisions around the Brainstorm Tool

Here are some concepts related to the Brainstorm tool.

1. The Brainstorm tool accepts sessions for specific Divisions. Typical Divisions might include Events, Gaming, Panels, Readings, etc.
2. Not all Divisions will accept idea submissions. (For example, not very many cons accept submissions for Special Events). 
3. In many cons, different people work on different Divisions -- there's usually a different teams working on Panels and Gaming.
4. Because different teams work on different Divisions, they often want to open and close submissions at different times.
5. There's usually a relationship between Tracks and Divisions: in many Divisions, there's only one Track (for example, in many cons, Gaming only has one track). While we might trust con staff to choose the right tracks for the different divisions, the brainstorming tool tries to enforce these choices. We assume that a Track "belongs" to one Division.

## Database Set-up

The Brainstorm tool is configured using 5 main tables, shown below. As mentioned above, we don't currently have easy configuration of some of these tables, although the Divisions and Tracks tables can be configured using the "Edit Configuration Tables" option under the Admin menu item.

![Database Tables](./images/brainstorm_db.png)

Ensure the following data has been created.

### Con Information

The `con_info` and `perennial_con_info` tables contain information about the current con. To understand the distinction between the tables, consider that the `con_info` describes something like "WisCon 45" and the `perennial_con_info` table describes "WisCon". (Similarly, the `con_info` table would describe "ChiCon 8" and the `perennial_con_info` table would describe "WorldCon").

### Divisions and Tracks

Ensure that each Division that you want to use for Brainstorming has a corresponding Track. Each track that is associated with a Division will be included in that Division's track selection.

![The Brainstorm Main Screen](./images/brainstorm_screen.png)

Additionally, for each Division that you want to use for Brainstorming, assign an "external key" (e.g. an "external key" for Gaming might be "GAMING_BRAINSTORM")

### Key Dates

The `con_key_dates` table links the Divisions (by `external_key`) with the current con (by `con_id`) and provides the start and end dates of the brainstorm period.