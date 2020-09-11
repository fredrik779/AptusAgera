<html>
<head>
<link rel="stylesheet" type="text/css" href="./css/style.css">
</head>
<body class="extrasTitleBody" onClick="location.reload(true);">

<?php
    // Init
    $ini = parse_ini_file('app.ini');
    $url = $ini['nextMeetingRSSUrl'];

    $rss = new DOMDocument();
    $rss->load($url);
    foreach ($rss->getElementsByTagName('item') as $node) {
        $title = $node->getElementsByTagName('title')->item(0)->nodeValue;
        // Add a newline when ":" is mentioned
        $title = str_replace(": ", ": <br>", $title);
    }
?>

<div class="extrasTitleHeader">Välkommen till Brf Kungen 3!</div>
<img src="img/pin_map_icon&16.png"> Tornslingan 35<br>
<img src="img/mail_2_icon&16.png"> info@kungen3.se<br>
<img src="img/globe_2_icon&16.png"> kungen3.se<br>
<img src="img/calendar_2_icon&16.png"> <?php echo $title ?><br>
</body>
</html>
