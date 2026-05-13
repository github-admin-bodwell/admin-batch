<?php
require_once __DIR__.'/sendEmailClass.php';
$from = array(
  array('email' => 'boas@bodwell.edu', 'name' => 'Bodwell Admissions')
);
$subject = 'Your Bodwell application is almost complete…';
$cc = array();

$to = array(
  array('email' => 'chanho.lee@bodwell.edu', 'name' => 'chano')
);

$body =
<<<BODY
<p>
    Hi (Student’s Name),
</p>
<p>
    Thank you for submitting your application to attend Bodwell High School!
</p>
<p>
    I am (Marketer’s Name) and I will evaluate your application. Please upload
    the following missing documents to your online application for us to set up
    an interview with you.
</p>
<p>
    (Only Show Missing Documents)
</p>
<ol start="1" type="1">
    <li>
        Passport Copy (photo ID page)
    </li>
    <li>
        Transcript of your past 3 years
    </li>
    <li>
        Digital Passport Size Photo <em>(35 mm x 45 mm)</em>
    </li>
    <li>
        <a
            href="https://bodwell.edu/wp-content/uploads/2017/01/student-profile_2017-18_v02fillable.pdf"
        >
            Personal Profile
        </a>
    </li>
    <li>
        <a
            href="https://bodwell.edu/wp-content/uploads/2017/01/immunization-history_2017-18_v02fillable.pdf"
        >
            Immunization History Record
        </a>
    </li>
</ol>
<p>
To do this, please go to your    <a href="https://apply.bodwell.edu/">online application</a> and upload the
documents in the “<strong>Additional info</strong>” section and “    <strong>save</strong>” your application.
</p>
<p>
    If you need assistance, please check out our
    <a
        href="https://bodwell.canto.com/download/presentation/7ftedjp9q53q9a2ghi7vsc6232/original"
        title="https://bodwell.canto.com/download/presentation/7ftedjp9q53q9a2ghi7vsc6232/original"
    >
        Step-By-Step Guide
    </a>
    or our
    <a
        href="https://bodwell.canto.com/direct/video/kdsjrnvisl23v474vkp5d3v76t/KukBVkAQy3_S7Z5zlDaTlm9dopA/original?content-type=video%2Fmp4&amp;name=Application+Walk-Through+V2.mp4"
    >
        video
    </a>
    on how our application process works. You can also email me directly at
    (Marketer’s Email), if you have any questions or would like to setup a
    quick chat.
</p>
<p>
    Once all the documents are uploaded, an admissions officer or I will reach
    out to set up an interview with you within a week. The purpose of the
    interview is to get to know you better and evaluate your acceptability for
    our school. We will be asking questions to understand your transcript,
    personality and motivations for coming to Bodwell and Canada.
</p>
<p>
    Sincerely,
</p>
<p>
    (Marketer’s Name)
</p>
<p>
    <em>(Marketer’s Title)</em>
    <em> </em>
    <em></em>
</p>
<p>
    <img
        border="0"
        width="207"
        height="75"
        src="cid:school"
    />
</p>
BODY;
echo $body;
$send = sendEmail($from, $to, $cc, $subject, $body, $altBody = '');
if($send == true) {

}

 ?>
