<?php
require_once __DIR__.'/sendEmailClass.php';
$from = array(
  array('email' => 'boas@bodwell.edu', 'name' => 'Bodwell Admissions')
);
$subject = 'Greetings from Bodwell! Here is some information to help with your application';
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
    Thank you for beginning the online application process to attend Bodwell
    High School. You can continue your application where you left off at any
    time by clicking this <a href="http://apply.bodwell.edu/">link</a>.
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
    on how our application process works. You can also reach out to us by
    emailing <a href="mailto:admissions@bodwell.edu">admissions@bodwell.edu</a>
    if you have additional questions on admissions or Bodwell.
</p>
<p>
    You will start by clicking "<strong>Apply New</strong>" and filling in all
    the required fields. Later at the Upload step, you will need to provide us
    the following documents:
</p>
<ol start="1" type="1">
    <li>
        Passport Copy (photo ID page)
    </li>
    <li>
        Transcript of your past 3 years
    </li>
</ol>
<p>
    Once all the documents are uploaded and the application is submitted, we
    will then set up an interview with you within a week. The purpose of the
    interview is to get to know you better and evaluate your acceptability for
    our school. We will be asking questions to understand your transcript,
    personality and motivations for coming to Bodwell and Canada.
</p>
<p>
    Here are some information about Bodwell that we think you and your parents
    might be interested in:
</p>
<ul type="square">
    <li>
        <a href="https://bodwell.edu/partner-resources/">
            Brochures and Materials
        </a>
        (<em>published in 18 different languages</em>)
    </li>
    <li>
        <a href="https://bodwell.canto.com/v/EnglishPortal/landing?viewIndex=1">
            Photos &amp; Videos
        </a>
    </li>
    <li>
        <a href="https://bodwell.edu/admissions/reviews/">
            Testimonials &amp; Online Reviews
        </a>
    </li>
    <li>
        <a href="https://bodwell.edu/all_about_bodwell/campus-facilities/">
            Virtual Campus Tour
        </a>
    </li>
    <li>
        Social Media Accounts:
    </li>
</ul>
<p>
    <a href="https://www.facebook.com/bodwellhighschool/" style="">
        <img
            border="0"
            width="27"
            height="27"
            src="cid:facebook"
            alt="Icon

Description automatically generated"
        />
    </a>
    &nbsp;	&nbsp;
    <a href="https://www.instagram.com/mybodwell" style="">
        <img
            border="0"
            width="26"
            height="26"
            src="cid:instagram"
            alt="Icon

Description automatically generated"
        />
    </a>
    &nbsp;	&nbsp;
    <a href="https://www.youtube.com/user/bodwellcollege" style="">
        <img
            border="0"
            width="38"
            height="24"
            src="cid:youtube"
        />
    </a>
    &nbsp;	&nbsp;
</p>
<p>
    Sincerely,
</p>
<p>
    Ms. Megumi Uehara<em></em>
</p>
<p>
    <em>Assistant Director of Recruitment</em>
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
