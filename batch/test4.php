<!--- scheduled to send out at 10:00am on every Sunday --->
<cfset file1 = "e:\admin.bodwell.edu\bhs\schedule\holiday.txt">
<cfset thisdate = dateformat(dateadd("d", -6, now()), "yyyy-mm-dd")>
<!--- Only within current term --->
<cfquery name="getsemester" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
select semesterid, startdate, enddate from tblbhssemester where currentsemester = 'Y'
</cfquery>
<cfif now() gte getsemester.startdate and now() lte dateadd("d", 1, getsemester.enddate)>
<!--- Monday to Friday --->
<cfloop from="0" to="4" index="i">
<!--- check if holiday --->
<cfset hasholiday = "">
<cfif fileexists(file1)>
<cfloop index="line" file="#file1#">
<cfif dateformat(dateadd("d", i, thisdate), "yyyy-mm-dd") is line><cfset hasholiday = line></cfif>
</cfloop>
</cfif>
<cfif hasholiday is "">
<cfquery name="getdata#i#" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
SELECT        d.LastName + ', ' + d.FirstName as name, a.SubjectName,
              CASE block WHEN '0' THEN '0' WHEN '1' THEN 'A' WHEN '2' THEN 'B' WHEN '3' THEN 'C' WHEN '4' THEN 'D' WHEN '5' THEN 'E' WHEN '6' THEN 'S1' WHEN '7' THEN 'G' WHEN '8' THEN 'S2' WHEN '9' THEN 'S1+S2'
              WHEN '10' THEN 'G1' WHEN '11' THEN 'G2' END AS Block, '#dateformat(dateadd("d", i, thisdate), "yyyy-mm-dd")#' AS Sdate, a.SameBlock, d.Email3
FROM          tblBHSSubject AS a INNER JOIN
              tblStaff AS d ON a.TeacherID = d.StaffID
WHERE        (a.SemesterID = #getsemester.semesterid#) AND (a.Type <> 'C') AND (a.SDay LIKE '%#evaluate(dayofweek(dateadd("d", i, thisdate))-1)#%') AND d.Email3 <> '' AND
             ((SELECT TOP (1) b.SDate FROM tblBHSAttendanceRpt AS b
			  WHERE (b.SDate = CONVERT(DATETIME, '#dateformat(dateadd("d", i, thisdate), "yyyy-mm-dd")# 00:00:00', 102)) AND (b.SubjectID = a.SubjectID)) IS NULL) AND
			 ((SELECT TOP (1) C.SDate FROM tblBHSAttendanceRpt AS C INNER JOIN tblBHSSubject AS E ON C.SubjectID = E.SubjectID
			  WHERE (C.SDate = CONVERT(DATETIME, '#dateformat(dateadd("d", i, thisdate), "yyyy-mm-dd")# 00:00:00', 102)) AND (E.SameBlock = a.SameBlock)) IS NULL)
</cfquery>
</cfif>
</cfloop>
<cfset theunion = "">
<cfquery name="getdata" dbtype="query">
<cfloop from="0" to="4" index="i">
<cfset hasholiday = "">
<cfif fileexists(file1)>
<cfloop index="line" file="#file1#">
<cfif dateformat(dateadd("d", i, thisdate), "yyyy-mm-dd") is line><cfset hasholiday = line></cfif>
</cfloop>
</cfif>
<cfif hasholiday is "">
#theunion#
select * from getdata#i#
<cfset theunion = "UNION ALL">
</cfif>
</cfloop>
order by name, sdate, block, subjectname
</cfquery>
<cfoutput query="getdata" group="name">
<cfif Email3 neq "">
<cfsavecontent variable="emailcontent">Hi #name#,<br><br>

This is a summary of the dates and times where class attendance was not recorded this past week. Accurate and timely attendance taking is a critical responsibility, and we are asking for your full cooperation in ensuring this is done consistently moving forward.<br><br>

Incomplete attendance records create significant challenges for our VP, AP, and counselling teams when following up on student absences and can lead to confusion for families and errors in our reporting systems.<br><br>

Please treat this as a high-priority task in your daily routine. Your support is essential in maintaining the integrity of our student monitoring process.<br><br>

<cfset ttlnum = 0><cfset thedate = ""><cfset thisblock = "">
<table border="1" cellpadding="0" cellspacing="0">
<cfoutput><tr><td>#sdate#</td><td>Block #block#</td><td>#subjectname#</td></tr><cfif thedate is sdate and thisblock is block><cfelse><cfset ttlnum = ttlnum + 1></cfif><cfset thedate = sdate><cfset thisblock = block></cfoutput>
<tr><td colspan="3">Total: #ttlnum#</td></tr>
</table><br>

Thank you for your attention to this matter.<br><br>

Housam<br><br></cfsavecontent>
<cfquery name="addsendrecord" datasource="#session.dsname#" username="#session.dsnusername#" password="#session.dsnpassword#">
insert into tblsendemail (sendto, sendfrom, sendsubject, sendattach, sendmessage, senddate, schoolid, sendcc, sendbcc)
values ('#Email3#', 'no-reply@bodwell.edu', N'Missed attendance for week of #dateformat(thisdate, "mmmm dd")#', '', N'#emailcontent#', #now()#, 'WMA', 'hallis@bodwell.edu;angela.jay@bodwell.edu;shane.chaffey@bodwell.edu', '')
</cfquery>
</cfif>
</cfoutput>
</cfif>
