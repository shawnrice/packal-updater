<?php
// require_once("../first-run.php");
require_once("../functions.php");
firstRun();

?>
<html>
<head>
<meta charset="UTF-8">
<link href='assets/fonts/Montserrat/montserrat.css' rel='stylesheet' type='text/css'>
<link href='assets/fonts/Source Sans Pro/source.sans.pro.css' rel='stylesheet' type='text/css'>
<style>

body {
	font-family: 'Source Sans Pro';
	font-size: 12px;
	padding: 0;
	margin: 0;
	
}
.fancy-text input {
	line-height: 1.5em;
	font-size: 1.2em;
	height: 2em;
	padding: 1px 1em;
	text-align: center;
	background-color: #EDEDED;
	border: 2px solid #999;

}

.fancy-text {
		margin: 0 auto;
	position: relative;
	text-align: center;
}
.zero, .one, .two, .three, .four, .five, .six, .seven, .eight {
	font-size: 1.25em;
	position: relative;
	float: left;
	width: 540px;
	height: 500px;
	clear: both;
}
.one {
	left: 540px;
	top: -500px;
}
.two {
	left: 1080px;
	top: -1000px;
}
.three {
	left: 1620px;
	top: -1500px;
}
.four {
	left: 2160px;
	top: -2000px;
}
.five {
	left: 2700px;
	top: -2500px;
}
.six {
	left: 3240px;
	top: -3000px;
}
.seven {
	left: 3780px;
	top: -3500px;
}
.eight {
	left: 4320px;
	top: -4000px;
}
#body {
	top: 250px;
	margin: 0 300px;
	height: 474px;
	text-align: justify;
	width: 540px;
	overflow: hidden;

}

#canvas {
	height: 680px;
    width: 1100px;
    margin: 0px auto;
    padding: 20px;
    
}

#bot {
	width: 140px; height: 240px; /* Basically the height of the image */
	z-index: 1;
}
#bot img {
	width: 140px; height: 240px;
}

#title, #bot, #body {
	position: absolute;
	clear: none;
}

#title {
	height: 50%; width: 50%;
	z-index: 0;
	font-size: 15em;
	/*left: 380px; top: 15px;*/
	color: #360758;
	opacity: .6;
	
}

.bot-animation {
		-webkit-animation:myfirst 3s;
		transition-timing-function: linear;
	    
}

/* State positions for the robot */
.first {
	left: 850px;
}

.last {
	left: 250px;
	    -webkit-filter: grayscale(1);
}

@-webkit-keyframes myfirst /* Safari and Chrome */
{
from {
	left:850px; 
	-webkit-transform: rotateY(0deg);  
	-webkit-transform-style: preserve-3d;
    -webkit-filter: grayscale(0);
}
to {
	left:250px; 
	-webkit-transform: rotateY(360deg);  
	-webkit-transform-style: preserve-3d;
    -webkit-filter: grayscale(1);
}
	

}


#title {
    -webkit-filter: blur(0px);
    -webkit-animation: fadein linear 6.9s;
}

@-webkit-keyframes fadein {
   0% {    -webkit-filter: opacity(0%) 		blur(6px); 	left: 268px; color: #555 }
  50% {    -webkit-filter: opacity(0%) 		blur(4px);	left: 268px;  }
  80% {    -webkit-filter: opacity(60%)  	blur(1px);	left: 268px; color: #555}
  95% {    -webkit-filter: opacity(100%)  	blur(1px);	left: 268px; color: #360758}
 100% {    -webkit-filter: opacity(100%) 	blur(1px); 	left: 250px; color: #360758 }
}

.fadeout {
	-webkit-filter: opacity(0%) blur(10px);
    -webkit-animation: fadein linear 3.5s;
}

@-webkit-keyframes fadeout {
   0% {    	-webkit-filter: opacity(0%) 	blur(10px) grayscale(0);}
  50% { 	-webkit-filter: opacity(100%)  	blur(10px) grayscale(0);}
 100% {  	-webkit-filter: opacity(100%) 	blur(0px) grayscale(1);}
}

.nav {
	width: 100%;
	text-align: center;
	
}

.nav a {
	margin: 3px 35px;
	text-decoration: none;
}
#slide {
	position: absolute;
}

/* I think I hate the colors on these switches. Change. Tweak... */
.onoffswitch {
    position: relative; width: 90px;
    -webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
    margin: 0 auto;
}
.onoffswitch-checkbox {
    display: none;
}
.onoffswitch-label {
    display: block; overflow: hidden; cursor: pointer;
    border: 2px solid #999999; border-radius: 20px;
}
.onoffswitch-inner {
    width: 200%; margin-left: -100%;
    -moz-transition: margin 0.3s ease-in 0s; -webkit-transition: margin 0.3s ease-in 0s;
    -o-transition: margin 0.3s ease-in 0s; transition: margin 0.3s ease-in 0s;
}
.onoffswitch-inner:before, .onoffswitch-inner:after {
    float: left; width: 50%; height: 30px; padding: 0; line-height: 30px;
    font-size: 14px; color: white; font-family: Trebuchet, Arial, sans-serif; font-weight: bold;
    -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box;
}
.onoffswitch-inner:before {
    content: "ON";
    padding-left: 10px;
    background-color: #2FCCFF; color: #FFFFFF;
}
.onoffswitch-inner:after {
    content: "OFF";
    padding-right: 10px;
    background-color: #EEEEEE; color: #999999;
    text-align: right;
}
.onoffswitch-switch {
    width: 18px; margin: 6px;
    background: #FFFFFF;
    border: 2px solid #999999; border-radius: 20px;
    position: absolute; top: 0; bottom: 0; right: 56px;
    -moz-transition: all 0.3s ease-in 0s; -webkit-transition: all 0.3s ease-in 0s;
    -o-transition: all 0.3s ease-in 0s; transition: all 0.3s ease-in 0s; 
}
.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-inner {
    margin-left: 0;
}
.onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-switch {
    right: 0px; 
}
.fancy-select {
text-align: center;
}

.fancy-select select {
	background: #EEEEEE;
	border: 2px solid #999999; border-radius: 1.5em;
	width: 100px;
	font-size: 1.2em;
	line-height: 2em;
	padding: 0 0 0 1.4em;
	position: relative;
	top: -4px;
	   -webkit-appearance: none;

}

.slide-inner {
	height: 240px;	
}

</style>
<script src="http://localhost:7893/assets/js/jquery.min.js"></script>
<script src="http://localhost:7893/assets/js/jquery-ui/ui/jquery-ui.js"></script>

<script>
function slideleft() {
	$('#slide').animate({left: "-=540px"},400);
}
function slideright() {
	$('#slide').animate({left: "+=540px"},400);
}
$(document).ready(function(){
	$('#bot').addClass('background').delay(500).queue(function(next){
    	$(this).addClass('bot-animation')
		.addClass('last')
    	.removeClass('first');
    	next();
	});
	setTimeout(function(){slideleft();},7500);
});
</script>

<script src="http://localhost:7893/resources/js/keep-alive.js"></script>
<script src="http://localhost:7893/resources/js/jquery.min.js"></script>
<script src="http://localhost:7893/resources/js/jquery-ui/ui/jquery-ui.js"></script>

</head>

<body>
<div id="canvas">
	<div id="animation">
		<div id="title">Packal</div>
		<div id="bot" class="first">
			<img src="assets/images/packal-man.jpg">
		</div>
	</div>
	<div id="body">
		<div id="slide">
		<div class="zero">
			<div class="slide-inner"></div>
		</div>
		<div class="one">
			<div class="slide-inner">
				<div style='font-size: 2em; text-align: center;'>About</div>
				<p>This Workflow is a companion interface for the Alfred Workflow
				 and Theme repository Packal.org. It's a bit heavy with the UI,
				 but we're doing something a bit heavy. Future versions will make
				 this UI less necessary. Note, I can update your Workflows, but
				 you still have to bring me up before I can let you know if there
				 are any updates available.</p>
			 </div>
			 <div class="nav"><a href="#two" onclick="slideleft();">Next »</a></div>
		</div>
		<div class="two">
			<div class="slide-inner">
			<div style='font-size: 2em; text-align: center;'>Getting Started</div>
				<p>But, let's start by answering a few questions in order to make
				things run smoothly. You can change these settings later.</p>
			</div>
			<div class="nav"><a href="#one" onclick="slideright();">« Back</a> <a href="#three" onclick="slideleft();">Next »</a></div>
		</div>
		<div class="three">
			<div class="slide-inner">
			<div style='font-size: 2em; text-align: center;'>Backups</div>
				<p>When I update your Workflows, I can keep some backups on the off-chance
				that things go wrong.</p>
				<p>So, how many backups do you want me to keep for you?</p>
				<br />
				<div class='fancy-select'>
					<select id='backup' name='backup' onchange="writeConfig('backup');">
						<option value='0'>None</option>
						<option value='1'>One</option>
						<option value='2'>Two</option>
						<option selected value='3'>Three</option>
						<option value='4'>Four</option>
						<option value='5'>Five</option>
						<option value='6'>Six</option>
						<option value='7'>Seven</option>
						<option value='8'>Eight</option>
						<option value='9'>Nine</option>
					</select>
				</div>
			</div>
			<div class="nav"><a href="#two" onclick="slideright();">« Back</a> <a href="#four" onclick="slideleft();">Next »</a></div>
		</div>
		<div class="four">
			<div class="slide-inner">
			<div style='font-size: 2em; text-align: center;'>Automatic Update Controls</div>
				<p>You might downloaded or installed some Workflows from some place
				other than Packal, but, do you want me to control the updates for any of these
				Workflows if they are on Packal or when the are uploaded to Packal?</p>
				<p>&nbsp;</p>
				<div class="onoffswitch">
					<input id='auto_add' class="onoffswitch-checkbox" type='checkbox' checked onclick="writeConfig('auto_add');">
				    <label class="onoffswitch-label" for="auto_add">
				        <div class="onoffswitch-inner"></div>
				        <div class="onoffswitch-switch"></div>
				    </label>
				</div>
			</div>
			<div class="nav"><a href="#three" onclick="slideright();">« Back</a> <a href="#five" onclick="slideleft();">Next »</a></div>
		</div>
		<div class="five">
			<div class="slide-inner">
				<div style='font-size: 2em; text-align: center;'>Reporting</div>
				<p>I can send anonymous information back to Packal about your installed Workflows.
				We don't store any identifying information, but we use the data to make the website
				better and to let the Workflow authors know just how many people their Workflows
				have helped, so, would you allow me to send this information back to Packal.org?</p>
				<div class="onoffswitch">
					<input id='report' class="onoffswitch-checkbox" type='checkbox' checked	onclick="writeConfig('report');">
				    <label class="onoffswitch-label" for="report" >
				        <div class="onoffswitch-inner"></div>
				        <div class="onoffswitch-switch"></div>
				    </label>
				</div>
			</div>
			<div class="nav"><a href="#four" onclick="slideright();">« Back</a> <a href="#six" onclick="slideleft();">Next »</a></div>
		</div>
		<div class="six">
			<div class="slide-inner">
			<div style='font-size: 2em; text-align: center;'>Notification Style</div>
				<p>What sort of notifications do you want to see when we update those Workflows? The 'Native' option is something that comes bundled with this Workflow.</p>
				<p>&nbsp;</p>
				<div class='fancy-select'>
					<select id='notify' name='notify' onchange="writeConfig('notify');">
						<option>Native</option>
						<option>Growl</option>
						<option>OS X</option>
					</select>
				</div>
			</div>
			<div class="nav"><a href="#five" onclick="slideright();">« Back</a> <a href="#seven" onclick="slideleft();">Next »</a></div>
		</div>
		<div class="seven">
			<div class="slide-inner">
			<div style='font-size: 2em; text-align: center;'>Packal Username</div>
				<p>So, if you submit Workflows on Packal.org, I don't want to update them because you
				probably have the most recent version. If you have an account on Packal.org, just enter
				your username here, then we'll know not to touch them.</p>
				<div class='fancy-text'>
					<input id='username' onchange="writeConfig('username');" type='text' size='20' name='username' placeholder='username'>		
				</div>
			</div>
			<div class="nav"><a href="#six" onclick="slideright();">« Back</a> <a href="#eight" onclick="slideleft();">Next »</a></div>
		</div>
		<div class="eight">
			<div class="slide-inner">
			<br />
				<div style='text-align: center; font-size: 2em;'>That's it.</div>
			</div>
			<div class="nav"><a href="#seven" onclick="slideright();">« Previous</a> <a href="http://localhost:7893/scripts/configure.php">Done</a></div>
		</div>
		</div>
	</div>
</div>

</body>
</html>
