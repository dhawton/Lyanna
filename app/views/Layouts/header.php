<?php
use \ZJX\Auth\Auth;

\Lyanna\View\Bundle::Scripts("main");
global $user_info;
?>
<!doctype HTML>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Jacksonville ARTCC - VATSIM</title>
    <meta name="description" content="VATSIM's Jacksonville ARTCC">
    <meta name="keywords" content="VATSIM,VATUSA,ZJX,Jacksonville,Orlando,Jacksonville ARTCC,Virtual ATC">
    <meta name="author" content="Daniel A. Hawton">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    \Lyanna\View\Bundle::Styles("main");
    \Lyanna\View\Render::Styles();
    ?>
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="outer">
    <div class="header-2">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="logo">
                        <h1><a href="/"><i class="fa fa-paper-plane"></i> Jacksonville ARTCC</a></h1>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="navy">
                        <ul>
                            <li><a href="/">Home</a></li>
                            <li><a href="#">Pilots</a>
                                <ul>
                                    <li><a href="http://www.aircharts.org">Charts</a></li>
                                    <li><a href="/Weather">Weather</a></li>
                                    <li><a href="/Feedback">Feedback</a></li>
                                    <li><a href="http://www.flightaware.com/statistics/ifr-route/">IFR Routes</a></li>
                                </ul>
                            </li>
                            <li><a href="#">Controllers</a>
                                <ul>
                                    <li><a href="/ATC/Staff">Staff</a></li>
                                    <li><a href="/ATC/Roster">Roster</a></li>
                                    <li><a href="http://ids.zjxartcc.com">IDS</a></li>
                                    <li><a href="/Feedback">Feedback Archive</a></li>
                                    <li><a href="/ATC/Downloads">Downloads</a></li>
                                    <li><a href="/ATC/Publications">Publications</a></li>
                                    <li><a href="/ATC/Visiting">Visiting Application</a></li>
                                    <li><a href="http://awts.aircharts.org">Training System</a></li>
                                    <li><a href="/ATC/Statistics">Controller Stats</a></li>
                                </ul>
                            </li>
                            <li><a href="/Feedback">Feedback</a></li>
                            <li><a href="/forum">Forums</a></li>
                            <?php
                            if (!Auth::check()) {
                            ?>
                            <li><a href="/ATC/Login">Login</a></li>
                            <?php
                            } else {
                            ?>
                            <li>
                                <a href="#"><?=$user_info['name']?></a>
                                <ul>
                                    <li><a href="/Profile">My Profile</a></li>
                                    <li><a href="/Logout">Logout</a></li>
                                </ul>
                            </li>
                            <?php
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="main-block">