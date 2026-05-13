<?php
require_once __DIR__.'/sendEmailClass.php';
$from = array(
  array('email' => 'boas@bodwell.edu', 'name' => 'Bodwell Admissions')
);
$subject = 'Do you need help completing your Bodwell application?';
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
    Thank you for completing your application to attend Bodwell High School!
</p>
<p>
    An admissions officer or I will reach out to you within the week to set up
    an interview. The purpose of the interview is to get to know you better and
    evaluate your acceptability for our school. We will be asking questions to
    understand your transcript, personality and motivations for coming to
    Bodwell and Canada.
</p>
<p>
    If you have any questions or would like to setup a quick chat, please email
    me directly at (Marketer’s Email).
</p>
<p>
    We look forward to meeting you soon! Below is some information about
    Bodwell that we think you and your parents might be interested in:
</p>
<ol start="1" type="1">
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
</ol>
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
    Mr. Kaan Turker<em></em>
</p>
<p>
    <em>Bodwell Admissions Department</em>
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
