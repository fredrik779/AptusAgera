<html>
<head>
<link rel="stylesheet" type="text/css" href="./css/style.css">
</head>

<body>

<?php
    // Init
    $ini = parse_ini_file('app.ini');
    $url                       = $ini['newsRSSUrl'];
    $extrasSLUrl               = $ini['extrasSLUrl'];
    $extrasSLAutoCloseTime     = $ini['extrasSLAutoCloseTime'];
    $excludedCategories        = $ini['excludedCategories'];
    $delay                     = $ini['delayMs'];
    $delaySelected             = $ini['delayMsSelected'];
    $loadThumbnailFromRssFeed  = $ini['loadThumbnailFromRssFeed'];
    $i = 0;

    // Get RSS document
    $rss = new DOMDocument();
    $rss->load($url);
    $displayedArticles = 0;

    function categoryIsIncluded($categoryObject, $excludedCategories) {
        //print $node->nodeValue;
        foreach($categoryObject as $node) {
            if ($node->nodeValue == $excludedCategories){
                return false;
            }
        }
        return true;
    }

    $rssArticles = new DOMDocument('1.0', 'utf-8');
    $rssArticles->loadXML("<root></root>");
    foreach ($rss->getElementsByTagName('item') as $node) {
        if (!categoryIsIncluded($node->getElementsByTagName('category'), $excludedCategories)){
            continue;
        }
        $nodeCopy = $rssArticles->importNode($node, true);
        $rssArticles->documentElement->appendChild($nodeCopy);
        $displayedArticles++;
    }
?>

<div class="main">
    <div class="newsMain">
        <div class="newsThumbnails">
            <?php

            foreach ($rssArticles->getElementsByTagName('item') as $node) {
                $title = $node->getElementsByTagName('title')->item(0)->nodeValue;
                $link = $node->getElementsByTagName('link')->item(0)->nodeValue;
                $desc = $node->getElementsByTagName('encoded')->item(0)->nodeValue;
                $imgUrl = "";

                if ($loadThumbnailFromRssFeed){
                    $doc = new DOMDocument();
                    $doc->loadHTML($desc, LIBXML_NOWARNING|LIBXML_NOERROR); // Exclude warnings due to custom HTML tabs: figure (from Wordpress) and postThumbnailUrl
                    $selector = new DOMXPath($doc);
                    $result = $selector->query('//postthumbnailurl');
                    foreach ($result as $node){
                        $imgUrl = "url(\"" . $node->getAttribute('src') . "\")";
                    }
                }else{
                    // Slower version, load the full post from WordPress and find the featured image
                    $html = file_get_contents($link);
                    $doc = new DOMDocument();
                    $doc->loadHTML($html);
                    $selector = new DOMXPath($doc);
                    $result = $selector->query('//img[@class="attachment-neve-blog size-neve-blog wp-post-image"]');
                    $imgUrl = "";
                    foreach ($result as $node){
                        $imgUrl = "url(\"" . $node->getAttribute('src') . "\")";
                    }
                }

                print "<div class='newsThumbnail' style='background-image: " . $imgUrl . ";' id='thumbnail" . $i . "' onClick='selectTab($i, $displayedArticles);'>";
				print "</div>";
                $i++;
            }
            ?>

			<!-- Progress indicator for slideshow -->
			<div id='slideshowProgress' class='slideshowProgressVisible'><div style='transition-duration: width <?php echo $ini['delayMsSelected'];?>ms;' id='slideshowProgressWaitProgressMarker'></div></div>

			<!-- Button to resume slideshow -->
			<div id='slideshowResume' onClick="nextArticle();" class=''>Fortsätt bläddring<div id="slideshowResumeImg"></div></div>
		
        </div>
        <!-- List each article from RSS feed -->
        <?php
            $i = 0;
            foreach ($rssArticles->getElementsByTagName('item') as $node) {

                $title = $node->getElementsByTagName('title')->item(0)->nodeValue;
                $dateObj = date_create($node->getElementsByTagName('pubDate')->item(0)->nodeValue);
                $pubDate = date_format($dateObj, "Y-m-d");
                $desc = $node->getElementsByTagName('encoded')->item(0)->nodeValue;
                
                print "<div class='newsContainer' id='article" . $i . "'>";
                    print "<div class='newsTitle'>" . $title . "</div>";
                    print "<div class='newsContent'>". $desc ."</div>"; 
                    print "<div class='newsFooter'>";
                        print "<div class='logo'><img class='logo' src='img/Logga.png'></div>"; 
                        print "<div class='newsPublishTime'>Publicerad: ". $pubDate ."</div>"; 
                    print "</div>";
                print "</div>";
                $i++;
            }
        ?>
        <!-- An object to cover the articles and prevent clicks -->
        <div id="preventArticleClick"> </div>
		
        <div id="divSLAptus" onclick="toggleSL();" class="extrasSLContent" >
            <img id="idSLLoading" class="extraSLLoading" src="img/loading.gif">
            <button class="extrasSLCloseButton">Stäng</button>
            <iframe id="iframeSLAptus" class="aptusAgeraSlFrame" src="" onLoad="document.getElementById('idSLLoading').style.visibility = 'hidden';"></iframe>
        </div>
    </div>
    <div class="extrasMain">
        <div class="extrasClock" onClick="showServiceMenu();">
            <div id="time"></div>
            <div id="date" ></div>
        </div>
        <div class="extrasSL" onClick="toggleSL(1)">
            <img class="extraSLButton" src="img/SL_logo2.PNG"><br>
            Visa avgångar
        </div>
        <div class="extrasWeather">
            <!-- Copied from vackertvader.se -->
            <div id='wrapper-FoBb'><span id='h2-FoBb'><a id='url-FoBb' href="//www.vackertvader.se/trångsund"> Trångsund</a></span><div id='load-FoBb'></div><a id='url_detail-FoBb' href="//www.vackertvader.se/trångsund">Detaljerad prognos</a></div><script type="text/javascript" src="//widget.vackertvader.se/widgetv3/widget_request/2667569?bgcolor=333333&border=none&days=5&key=-FoBb&lang=&maxtemp=yes&size=160v3x&textcolor=ffffff&unit=C&wind=yes" charset="utf-8"></script>
            <!-- Vit bakgrund: 
            <div id='wrapper-alWP'><span id='h2-alWP'><a id='url-alWP' href="//www.vackertvader.se/trångsund"> Trångsund</a></span><div id='load-alWP'></div><a id='url_detail-alWP' href="//www.vackertvader.se/trångsund">Detaljerad prognos</a></div><script type="text/javascript" src="//widget.vackertvader.se/widgetv3/widget_request/2667569?bgcolor=ffffff&border=none&days=5&key=-alWP&lang=&maxtemp=yes&size=160v3x&textcolor=363636&unit=C&wind=yes" charset="utf-8"></script>
            -->
            <div id="preventWeatherClick"> </div>
        </div>
    </div>
</div>
<div id="toolsPanel" style="visibility: hidden; top: -500px;">
    <div class="toolsHeader">
        <span><b>SERVICEMENY</b></span>
        <span id="localStorageTestResult"></span>
    </div>
    <button class="toolsButtonStatic toolsButton" onclick="hideServiceMenu();">&#9664; Stäng menyn</button>
    <button class="toolsButtonStatic toolsButton" onClick="localStorage.setItem('aptusAgeraCacheAge', ''); location.reload(true);">&#128465; Tvinga synk med hemsidan</button>
    <button class="toolsButtonStatic toolsButton" onclick="location.reload(true);">&#11118; Ladda om</button>
    <button class="toolsButton" onClick="getElementById('toolsFrame').src = '<?php echo $ini['tool1Url'] ?>';"><?php echo $ini['tool1Name'] ?></button>
    <button class="toolsButton" onClick="getElementById('toolsFrame').src = '<?php echo $ini['tool2Url'] ?>';"><?php echo $ini['tool2Name'] ?></button>
    <button class="toolsButton" onClick="getElementById('toolsFrame').src = '<?php echo $ini['tool3Url'] ?>';"><?php echo $ini['tool3Name'] ?></button>

    <script>
        // Check browser support
        if (typeof(Storage) !== "undefined") {
            if (localStorage.getItem("aptusAgeraCacheAge") == null || localStorage.getItem("aptusAgeraCacheAge") == ""){
                document.getElementById("localStorageTestResult").innerHTML = "Cache date (localStorage): (not found)";
            }else{
                console.log(parseInt(localStorage.getItem("aptusAgeraCacheAge")))
                var date = new Date(parseInt(localStorage.getItem("aptusAgeraCacheAge"))); 
                console.log(date.toString())
                document.getElementById("localStorageTestResult").innerHTML = "Cache date (localStorage): " + date.toLocaleString();
            }
        } else {
            document.getElementById("localStorageTestResult").innerHTML = "localStorage is NOT supported";
        }
    </script>
    <iframe id="toolsFrame"></iframe>
</div>

<script>
var currentArticle = -1;
var timeout;
var timeoutServiceMenu;
var delay = <?php echo $delay ?>;
var delaySelected = <?php echo $delaySelected ?>;
var totalArticles = <?php echo $displayedArticles ?>;

function selectTab(i, totalNr){
    console.log("selectTab(" + i + "/" + totalNr + ")"); 
    clearTimeout(timeout);
	startSlideshowProgressMarker((delaySelected/1000), i, totalNr);
	
	// Show "Play" button
	document.getElementById("slideshowResume").classList.add("slideshowResumeVisible");

    for (id = 0; id < totalNr; id++){
        if (i == id){
            //console.log("SHOW: " + id + ": " + i);
            document.getElementById("article" + id).style.display = "flex";
            document.getElementById("thumbnail" + id).classList.add("newsThumbnailActive");
            currentArticle = i;
        }else{
            //console.log("HIDE: " + id + ": " + i);
            document.getElementById("article" + id).style.display = "none";
            document.getElementById("thumbnail" + id).classList.remove("newsThumbnailActive");
        }
    }

    // Set timer to show next article
    timeout = setTimeout(nextArticle, delaySelected)
}

function showServiceMenu(){
	var panel = document.getElementById('toolsPanel'); 
	panel.style.visibility = 'visible'; 
	panel.style.top = '0px';	
	
	// Hide the service menu after X ms
	timeoutServiceMenu = setTimeout(hideServiceMenu, 120000);
}

function hideServiceMenu(){
	var panel = document.getElementById('toolsPanel'); 
	panel.style.visibility = 'hidden'; 
	panel.style.top = '-1500px';
}


function nextArticle() {
    console.log("nextArticle()");
	var i = 0
    if (currentArticle == (totalArticles - 1)){;
       selectTab(i, totalArticles);
    }else{
		i = currentArticle + 1
       selectTab(i, totalArticles);
    }
    clearTimeout(timeout);
    timeout = setTimeout(nextArticle, delay)

	resetSlideshowProgressMarker();
	startSlideshowProgressMarker((delay/1000), i, -1);
}

function resetSlideshowProgressMarker(){
	//document.getElementById("slideshowProgress").classList.remove("resumeSlideshowVisible");
	//document.getElementById("slideshowProgress").classList.add("resumeSlideshowHidden");
	
	document.getElementById("slideshowProgressWaitProgressMarker").style.transitionDuration = "0s";
	document.getElementById("slideshowProgressWaitProgressMarker").style.width = "0px";

	// Hide "Play" button
	document.getElementById("slideshowResume").classList.remove("slideshowResumeVisible");
}

function startSlideshowProgressMarker(delay, i, totalNr){
	resetSlideshowProgressMarker()
	
	document.getElementById("slideshowProgress").style.top = document.getElementById("thumbnail" + i).offsetTop;
	document.getElementById("slideshowProgress").style.left = document.getElementById("thumbnail" + i).offsetLeft;

    //document.getElementById("slideshowProgress").classList.add("resumeSlideshowVisible");
    //document.getElementById("slideshowProgress").classList.remove("resumeSlideshowHidden");

	document.getElementById("slideshowProgressWaitProgressMarker").style.transitionDuration = "" + delay + "s";
    document.getElementById("slideshowProgressWaitProgressMarker").style.width = "100%";
	
	// Copy the onclick event from the current thumbnail
	document.getElementById('slideshowProgress').onclick = document.getElementById("thumbnail" + i).onclick;
}


function updateClock() {
    var today = new Date();
    var dayNr = today.getDate();
    var h = today.getHours();
    var m = today.getMinutes();
    h = formatTime(h);
    m = formatTime(m);
    document.getElementById('time').innerHTML = h + ":" + m;
    
    // Use separate options, to prevent display of "den": e.g. "mon, den 8 april"
    var optionsDay = { weekday: 'short' };
    var optionsMon = { month: 'short' };
    //var options = { weekday: 'short', month: 'long', day: 'numeric' };
    document.getElementById('date').innerHTML = today.toLocaleDateString("sv-SE", optionsDay) + " " + dayNr + " " + today.toLocaleDateString("sv-SE", optionsMon);
    var t = setTimeout(updateClock, 5000);
}
function formatTime(i) {
    if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
    return i;
}

var slAutoCloseTimer;

function toggleSL(show){
	var frame = document.getElementById("iframeSLAptus");
	var div = document.getElementById("divSLAptus");
    console.log(show)
	if (show == 1){
		div.style.visibility = "visible";
		frame.style.visibility = "visible";
        document.getElementById('idSLLoading').style.visibility = 'visible';        
		frame.src = "<?php echo $extrasSLUrl ?>";
        // Auto close
        window.clearTimeout(slAutoCloseTimer);
	    slAutoCloseTimer = window.setTimeout(toggleSL, <?php echo $extrasSLAutoCloseTime ?>*1000);
	}else{
        document.getElementById('idSLLoading').style.visibility = 'hidden';
		div.style.visibility = "hidden";
		frame.style.visibility = "hidden";
		frame.src = "";
	}
}

// Starta timer och visa första artikeln
nextArticle();
updateClock();

// Se till att servicemenyn är stängd. IBLAND har den visats när tavlan väcks
hideServiceMenu();
</script>

</body>
</html>
