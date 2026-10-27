# PHP 8.2.35 batch migration checklist

Updated: 2026-10-27. No production URL calls or production-recipient sends were performed.

Test policy: fictional data only for delivery; To chanho.lee@bodwell.edu; if the original branch has CC, replace with kwyes2@hotmail.com; always empty BCC. Original production recipients and schedule conditions remain in source. Real-data validation intercepts every mail call and permits SELECT statements only.

| File | Shared .env DB | PHP 8.2.35 | Read-only real-data validation | Fictional test email |
|---|---|---|---|---|
| generateUserAuth.php (root) | Done | Pass; NULL/blank/quoted names/empty results exercised | Pending: real writes never executed | Not applicable |
| activitypayment.php | Done | Pass | Current overdue branch passed; other date branches pending | Received by user |
| rptemailattendance.php | Done | Pass | Passed; 263 SELECTs, 7 messages intercepted (slow sequential queries) | SMTP accepted; receipt pending |
| dailyattendance.php | Done | Pass | Monday Y periods 1-5 passed; Monday N periods 1-5 correctly skipped | SMTP accepted; receipt pending |
| adminalertgrade.php | Done | Pass | Passed; 1 message intercepted | SMTP accepted; receipt pending |
| alertgrade.php | Done | Pass | Passed; 8 messages intercepted | SMTP accepted; receipt pending |
| boardingalertgrade.php | Done | Pass | Passed; 9 messages intercepted | SMTP accepted; receipt pending |
| dormbedtimeemailrpt.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| dormbedtimeemailweekdayrpt.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| dormbedtimeemailweekendrpt.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| dormlatesickemailrpt.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| dormlatesickemailrpt2.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| dormstudyemailrpt3.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| dormstudyemailrpt2.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| dormtidinessemailrpt.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| englishtutorial.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| emailexcessiveabsences.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint currently unreachable | Not sent, per user instruction |
| emailexcessiveabsences2.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint unreachable | Not sent, per user instruction |
| newenrolment.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint unreachable | Not sent, per user instruction |
| rptemailremote.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint unreachable | Not sent, per user instruction |
| studentcsvformat.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint unreachable | Not applicable |
| rptinternationalexpiry.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint unreachable | Not sent, per user instruction |
| outstandingfee.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint unreachable | Not sent, per user instruction |
| weeklyattendance.php | Done | Pass; synthetic populated/empty rows passed | Pending: DB TCP endpoint unreachable | Not sent, per user instruction |

## Current group

- All five files passed strict-warning fictional-data execution using their own rendering code.
- dailyattendance: ten synthetic combinations passed (Y on Monday, N on Tuesday; periods 1-5). Actual Tuesday-Friday results still depend on live schedule data.
- rptemailattendance: both counselor report and daily summary were exercised with fictional rows; one counselor-report sample was mailed.
- The five emailed samples had no active original CC; all five were sent To only, without BCC.
- Grade reports initialize empty result arrays; admin and boarding reports initialize CC. Counselor lookup uses bound parameters and the shared connection.
- sendEmailClass.php now accepts optional BCC as argument 7. dailyattendance passes an empty string as argument 6 and its original BCC array as argument 7.
- Date/time conditions and production recipient lists were preserved. No scheduler was invoked.
## Second group of ten (2026-10-27)

- No emails sent, no production URLs called, no database rows changed.
- All ten migrated to shared .env DB configuration and passed PHP 8.2.35 syntax checks and strict-warning synthetic execution with both populated and empty query results.
- 35 weekday assertions confirmed existing active/inactive day rules for weekday/weekend curfew, study reminder, tidiness, and English tutorial jobs.
- Actual-data execution could not be completed: first DB connection failed; a separate 4-second TCP probe could not reach the configured database endpoint. Remaining actual DB attempts were stopped rather than repeated.
- Tidiness report now initializes its DB connection and splits multiple recipients into separate addresses. Missing optional advisor email is omitted from sick report. The bedtime comment guard was corrected.
- English tutorial checks SELECT results with fetchColumn rather than unreliable rowCount; the original holiday path is preferred, with batch/holiday.txt fallback when it is absent. Missing/unreadable holiday files fail clearly.
- Study reminder and excessive-absence jobs only record sent logs after successful sendEmail return. Tests intercept both mail delivery and INSERT INTO tblSendEmail; no real writes occur.
- Excessive-absence recipient names now default to an empty string as required by the common mail wrapper.
- Code migration total: 16 of 24 files. Remaining code migration: 8 files. Actual-data validation of these ten remains pending network access.

## Final group of eight (2026-10-27)

- All 24 listed files now use the shared .env database connection; zero code migrations remain. This does not mean live validation is complete.
- Final eight passed PHP 8.2.35 syntax checks and 16 populated/empty synthetic executions. All email calls, database mutations and CSV writes were intercepted.
- generateUserAuth: missing/NULL/blank PEN or SchoolEmail is skipped; bound INSERT handles apostrophes and NULL parent names. Existing password generation/policy and student selection are retained. No-password console output; no account was actually created.
- rptemailremote: corrected the existing single-array call to match sendEmail's positional arguments; failed delivery cannot record a successful log. Its existing day/hour condition is unchanged.
- NULL date/number/string compatibility addressed in international-expiry, outstanding-fee and weekly-attendance formatting; blank expiry dates display blank.
- studentcsvformat aborts if there is no current semester instead of overwriting the export with invalid context; original CSV columns and destination unchanged. Actual file write was blocked during tests.
- emailexcessiveabsences2: recipient name initialized for common wrapper. Its existing EA30 UPDATE order remains unchanged; the potential failure-before-delivery behavior is a pre-existing business issue, not silently changed.
- newenrolment DELETE/INSERT behavior and weeklyattendance queue INSERT behavior were retained and intercepted in tests. No statistics or queue rows changed.
- 28 day/hour assertions passed for remote reports and weekday excessive-absence notifications.
- DB TCP probe still failed within four seconds; real-data validation of the latest 18 files remains pending connectivity. No production URL calls or mail deliveries in these final groups.
- Existing rptinternationalexpiry comment about three unimplemented global sections remains; no new report sections or schedule rules were invented.
