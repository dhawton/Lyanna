<?php
\Lyanna\View\View::showHeader();
?>
<div class="r-slider">
    <div class="bannercontainer">
        <div class="banner">
            <ul>
                <li data-transition="random-static" data-slotamount="5">
                    <img src="/assets/images/slides/home/1.jpg" alt="" data-bgfit="cover" data-bgposition="center center"
                         data-bgrepeat="no-repeat">
                </li>
                <li data-transition="random-static" data-slotamount="5">
                    <img src="/assets/images/slides/home/2.jpg" alt="" data-bgfit="cover" data-bgposition="center center"
                         data-bgrepeat="no-repeat">
                </li>
                <li data-transition="random-static" data-slotamount="5">
                    <img src="/assets/images/slides/home/3.jpg" alt="" data-bgfit="cover" data-bgposition="center center"
                         data-bgrepeat="no-repeat">
                </li>
                <li data-transition="random-static" data-slotamount="5">
                    <img src="/assets/images/slides/home/4.jpg" alt="" data-bgfit="cover" data-bgposition="center center"
                         data-bgrepeat="no-repeat">
                </li>
                <li data-transition="random-static" data-slotamount="5">
                    <img src="/assets/images/slides/home/5.jpg" alt="" data-bgfit="cover" data-bgposition="center center"
                         data-bgrepeat="no-repeat">
                </li>
                <li data-transition="random-static" data-slotamount="5">
                    <img src="/assets/images/slides/home/6.jpg" alt="" data-bgfit="cover" data-bgposition="center center"
                         data-bgrepeat="no-repeat">
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="container">
    <br>
    <div class="about-us-one">
        <div class="row">
            <div class="col-md-12">
                <h2>Virtual Jacksonville ARTCC</h2>
                <p>Welcome to the Jacksonville ARTCC website. This website is for a group of online hobbyists who
                    partake in simulated flying and air traffic control on the VATSIM network. The Jacksonville ARTCC
                    owns a large chunk of airspace spanning from the Pensacola Naval Air Station complex, to Jacksonville
                    International airport; Charleston Air Force Base/International Airport, down to Orlando International
                    airport. The procedures we use mirror, to an extent, those utilized by real world air traffic control.
                    <strong>At no time, however, should a procedure, chart, or other information contained on this website be used
                    for real world navigation.</strong></p>
            </div>
        </div>
    </div>
    <div class="divider-1"></div>
    <div class="row">
        <div class="col-md-12">
            <h2><i class="fa fa-newspaper-o"></i> News</h2>
            <p>Announcements will be fed here.</p>
        </div>
    </div>
    <div class="divider-1"></div>
    <div class="row">
        <div class="col-md-12">
            <h2><i class="fa fa-calendar"></i> Events</h2>
            <p>Events will be listed here</p>
        </div>
    </div>
    <div class="divider-1"></div>
    <div class="row">
        <div class="col-md-6">
            <h2><i class="fa fa-cloud"></i> Weather</h2>
            <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Wind</th>
                    <th>Altim.</th>
                </thead>
                <tbody id="tableWeather">
                    <tr>
                        <td colspan="4"><img src="/assets/images/spinner_radar.gif"> Loading...</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
        <div class="col-md-6">
            <h2><i class="fa fa-search"></i> Who's Online?</h2>
            <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead>
                <th>Position</th>
                <th>Frequency</th>
                <th>Controller</th>
                </thead>
                <tbody id="tableControllers">
                <tr>
                    <td colspan="3"><img src="/assets/images/spinner_radar.gif"> Loading...</td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<?php
\Lyanna\View\Bundle::Scripts("home");

\Lyanna\View\View::showFooter();