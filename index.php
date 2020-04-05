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
    $delay                     = $ini['delay'];
    $loadThumbnailFromRssFeed  = $ini['loadThumbnailFromRssFeed'];
    $i = 0;

    // Get RSS document
    $rss = new DOMDocument();
    $rss->load($url);
    $displayedArticles = 0;

    function categoryIsIncluded($categoryObject, $excludedCategories) {
        if ($categoryObject->item(0)->nodeValue == $excludedCategories){
            return false;
        }  
        if ($categoryObject->item(1)->nodeValue == $excludedCategories){
            return false;
        }
        // TODO: Enumerate
        //foreach ($categoryObject->items as $cat) {
        //    if ($cat->nodeValue == $excludedCategories){
        //        print $cat;
        //        print $cat->nodeValue;
        //        return false;
        //    }
        //}
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
            $imgUrl = "";

            foreach ($rssArticles->getElementsByTagName('item') as $node) {
                $title = $node->getElementsByTagName('title')->item(0)->nodeValue;
                $link = $node->getElementsByTagName('link')->item(0)->nodeValue;
                $desc = $node->getElementsByTagName('encoded')->item(0)->nodeValue;

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

                print "<div class='newsThumbnail' style='background-image: " . $imgUrl . ";' id='thumbnail" . $i . "' onClick='selectTab($i, $displayedArticles);'></div>";
                $i++;
            }
            ?>
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
        <div class="extrasClock" onClick="location.reload(true);">
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

<script>
var currentArticle = -1;
var timeout;
var delay = <?php echo $delay ?>;
var totalArticles = <?php echo $displayedArticles ?>;
function selectTab(i, totalNr){
    console.log("selectTab(" + i + ", " + totalNr); 
    clearTimeout(timeout);

    for (id = 0; id < totalNr; id++){
        if (i == id){
            console.log("SHOW: " + id + ": " + i);
            document.getElementById("article" + id).style.display = "flex";
            document.getElementById("thumbnail" + id).classList.add("newsThumbnailActive");
            currentArticle = i;
        }else{
            console.log("HIDE: " + id + ": " + i);
            document.getElementById("article" + id).style.display = "none";
            document.getElementById("thumbnail" + id).classList.remove("newsThumbnailActive");
        }
    }

    // Set timer to show next article
    timeout = setTimeout(nextArticle, delay)
}

function nextArticle() {
   if (currentArticle == (totalArticles - 1)){;
      selectTab(0, totalArticles);
   }else{
      selectTab(currentArticle + 1, totalArticles);
   }
   timeout = setTimeout(nextArticle, delay)
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
    var optionsMon = { month: 'long' };
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
</script>

</body>
</html>
