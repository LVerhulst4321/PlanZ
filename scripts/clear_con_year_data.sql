/* This MySQL script will clear out all the data from a PlanZ database for a single year's con, but
   leave all configuration and past year data in place. */


TRUNCATE TABLE PreviousParticipants;
TRUNCATE TABLE Schedule;
TRUNCATE TABLE ParticipantAvailabilityDays;
TRUNCATE TABLE ParticipantAvailabilityTimes;
TRUNCATE TABLE ParticipantAvailability;
TRUNCATE TABLE ParticipantHasRole;
TRUNCATE TABLE ParticipantInterests;
TRUNCATE TABLE ParticipantOnSession;
TRUNCATE TABLE ParticipantSessionInterest;
TRUNCATE TABLE ParticipantSuggestions;
TRUNCATE TABLE SessionEditHistory;
TRUNCATE TABLE SessionHasFeature;
TRUNCATE TABLE SessionHasPubChar;
TRUNCATE TABLE SessionHasService;
TRUNCATE TABLE Sessions;
TRUNCATE TABLE EmailQueue;
TRUNCATE TABLE UserHasPermissionRole;
TRUNCATE TABLE Participants;
TRUNCATE TABLE CongoDump;
