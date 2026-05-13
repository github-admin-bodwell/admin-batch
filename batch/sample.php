<!--- Acad. Admin --->
<cfquery name="getAAdmin" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1001%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset AAdminEmail = valuelist(getAAdmin.email3, ";")>
<!--- all boarding admin (Robyn, Danika, Jordan, Jamie) --->
<cfquery name="getBoadingAdmin" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3, FirstName + ' ' + LastName as FullName
FROM     tblStaff
WHERE    (GroupTeam like '%1003%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset BAemail = valuelist(getBoadingAdmin.email3, ";")>
<!--- Housam requested to add him under Boarding Admin for IA-PD 2021-10-05 --->
<!--- all boarding admin (Robyn, Danika, Jordan, Jamie) + Housam--->
<cfquery name="getBoadingAdmin1" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1003%' or GroupTeam like '%1004%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset BAemail1 = valuelist(getBoadingAdmin1.email3, ";")>
<!--- Nursing team --->
<cfquery name="getNursingteam" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3, FirstName + ' ' + LastName as FullName
FROM     tblStaff
WHERE    (GroupTeam like '%1002%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Nursingteam = valuelist(getNursingteam.email3, ";")>
<!--- Language Development 2021-11-16 --->
<cfquery name="getLD" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1016%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset LDEmail = valuelist(getLD.email3, ";")>
<!--- Post-secondary 2023-10-06 --->
<cfquery name="get1017" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1017%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1017 = valuelist(get1017.email3, ";")>
<!--- Support Services Katherine --->
<cfquery name="getSS" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1005%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset SSEmail = valuelist(getSS.email3, ";")>
<!--- Administrative Assistant - Laise Lo Prete 2021-10-28 --->
<cfquery name="getKeyFOB" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1009%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset KeyFOBemail = valuelist(getKeyFOB.email3, ";")>
<!--- Library booking from vehicle & facility booking 2021-10-22 --->
<cfquery name="getLibB" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1011%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset LibBemail = valuelist(getLibB.email3, ";")>
<!--- Quarantine info for PEN number 2021-11-08 --->
<cfquery name="getQua" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1013%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset PenEmail = valuelist(getQua.email3, ";")>
<!--- Add / Edit Subject --->
<cfquery name="get1014" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1014%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1014 = valuelist(get1014.email3, ";")>
<!--- TOC (staff.bodwell.edu/tocconfirmed.cfm) 2021-11-12 --->
<cfquery name="getTOCEmail" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1015%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset TOCEmail = valuelist(getTOCEmail.email3, ";")>
<!--- ID Card 2021-11-12 --->
<cfquery name="getIDCEmail" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1010%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset IDCEmail = valuelist(getIDCEmail.email3, ";")>
<!--- Discipline 2021-11-19 --->
<cfquery name="getDisciEmail" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1018%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset DisciEmail = valuelist(getDisciEmail.email3, ";")>
<!--- MSP 1 counselor 2022-02-28 --->
<cfquery name="getMSP1Counselor" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1027%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset MSP1Counselor = valuelist(getMSP1Counselor.FirstName, ";")>
<!--- MSP 1 2021-11-29 --->
<cfquery name="getMSP1Email" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1019%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset MSP1Email = valuelist(getMSP1Email.email3, ";")>
<!--- MSP 2 counselor 2022-02-28 --->
<cfquery name="getMSP2Counselor" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1028%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset MSP2Counselor = valuelist(getMSP2Counselor.FirstName, ";")>
<!--- MSP 2 2021-11-29 --->
<cfquery name="getMSP2Email" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1020%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset MSP2Email = valuelist(getMSP2Email.email3, ";")>
<!--- MSP 3 2021-11-29 --->
<cfquery name="getMSP3Email" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1021%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset MSP3Email = valuelist(getMSP3Email.email3, ";")>
<!--- MSP bcc 2021-11-29 --->
<cfquery name="getMSPbccEmail" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1022%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset MSPbccEmail = valuelist(getMSPbccEmail.email3, ";")>
<!--- Email Bounced 2021-12-06 --->
<cfquery name="getITEmail" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1023%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset ITEmail = valuelist(getITEmail.email3, ";")>
<!--- Remote Email Bounced 2021-12-29 --->
<cfquery name="getRemoteEmail" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1025%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset RemoteEmail = valuelist(getRemoteEmail.email3, ";")>
<!--- Accounting 2022-02-22 --->
<cfquery name="getAccEmail" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1012%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset AccEmail = valuelist(getAccEmail.email3, ";")>
<!--- Sick report excused absences email for S block Instructor 2022-03-01 --->
<cfquery name="getSickEmail" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1029%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset SickEmail = valuelist(getSickEmail.email3, ";")>
<!--- NOT current student changed to current student 2022-03-09 --->
<cfquery name="get1031" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1031%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1031 = valuelist(get1031.email3, ";")>
<!--- Current student changed to NOT current student 2022-03-09 --->
<cfquery name="get1032" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1032%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1032 = valuelist(get1032.email3, ";")>
<!--- Check-in Message 2022-03-09 --->
<cfquery name="get1033" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1033%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1033 = valuelist(get1033.email3, ";")>
<!--- Parent/Guardian 1 email address has NOT been verified 2022-03-10 --->
<cfquery name="get1034" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1034%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1034 = valuelist(get1034.email3, ";")>
<!--- Re-registartion data 2022-03-10 --->
<cfquery name="get1035" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1035%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1035 = valuelist(get1035.email3, ";")>
<!--- Sick report entered as excused 2022-03-11 --->
<cfquery name="get1036" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1036%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1036 = valuelist(get1036.email3, ";")>
<!--- Quarantine status changed 2022-03-11 --->
<cfquery name="get1037" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1037%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1037 = valuelist(get1037.email3, ";")>
<!--- Attendance 2022-03-11 --->
<cfquery name="get1038" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1038%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1038 = valuelist(get1038.email3, ";")>
<!--- Homestay 2022-03-11 --->
<cfquery name="get1039" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1039%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1039 = valuelist(get1039.email3, ";")>
<!--- Fee Reminder/Term Break Fee Reminder/Stay Term Break/Airport Pick-up Confirmation Letter/Airport Drop-off Confirmation Letter 2022-03-11 --->
<cfquery name="get1040" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1040%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1040 = valuelist(get1040.email3, ";")>
<!--- test.bodwell.edu Online Test 2022-04-01 --->
<cfquery name="get1041" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1041%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1041 = valuelist(get1041.email3, ";")>
<!--- bus booking email 2022-04-05 --->
<cfquery name="get1042" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1042%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1042 = valuelist(get1042.email3, ";")>
<!--- Fee Reminder --->
<cfquery name="get1043" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1043%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1043 = valuelist(get1043.email3, ";")>
<!--- Schedule - Sick report (Email) --->
<cfquery name="get1044" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1044%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1044 = valuelist(get1044.email3, ";")>
<!--- Librarian --->
<cfquery name="get1045" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1045%')
ORDER BY FirstName, LastName
</cfquery>
<cfset LibrarianEmail = get1045.email3>
<!--- Graduation --->
<cfquery name="get1046" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1046%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1046 = valuelist(get1046.email3, ";")>
<!--- Study Hall Report (email) --->
<cfquery name="get1047" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1047%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1047 = valuelist(get1047.email3, ";")>
<!--- Excessive Absences (email) --->
<cfquery name="get1048" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1048%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1048 = valuelist(get1048.email3, ";")>
<!--- Insert New Credit (Transcript) --->
<cfquery name="get1049" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1049%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1049 = valuelist(get1049.email3, ";")>
<!--- Graduation info - Updated (Email) --->
<cfquery name="get1050" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1050%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1050 = valuelist(get1050.email3, ";")>
<!--- Information Share With (Medical) --->
<cfquery name="get1051" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3, FirstName + ' ' + LastName as FullName
FROM     tblStaff
WHERE    (GroupTeam like '%1051%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1051 = valuelist(get1051.email3, ";")>
<!--- Edit Credit (Transcript) --->
<cfquery name="get1052" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3, FirstName + ' ' + LastName as FullName
FROM     tblStaff
WHERE    (GroupTeam like '%1052%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1052 = valuelist(get1052.email3, ";")>
<!--- University/College - in progress (Email) 2023-04-24 --->
<cfquery name="get1053" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1053%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1053 = valuelist(get1053.email3, ";")>
<!--- Excessive Absences W/O S/F/A (email) --->
<cfquery name="get1054" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT   StaffID, FirstName, LastName, Email3
FROM     tblStaff
WHERE    (GroupTeam like '%1054%') and currentstaff = 'Y'
ORDER BY FirstName, LastName
</cfquery>
<cfset Email1054 = valuelist(get1054.email3, ";")>
