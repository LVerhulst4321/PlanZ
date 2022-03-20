## This script creates a view on the pronoun information to make it easier to 
## include pronouns in, for example, reports
##
##  Created by BC Holmes on March 20, 2022
##
create view ParticipantPronouns 
as select badgeid, case when pd.pronounid = 99 then pd.pronounother else pr.pronounname end as pronouns
from ParticipantDetails pd 
join Pronouns pr USING (pronounid);
