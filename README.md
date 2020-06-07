**Om programmet**
Web-program för att visa WordPress-artiklar på en AptusAgera-tavla. Artiklarna hämtas via RSS-flöde i WordPress.

**Starta lokalt**
1. Installera PHP
2. Dubbelklicka på genvägen 'Start local PHP server'
3. Dubbelklicka på genvägen 'Start local DEV page'

**Brf Kungen 3**
Mått på den del av tavlan som används för den här sidan:
1000 x 1024
Sidan är utvecklad för en tavla av typen Aptus Agera. Tavlan körs under Internet Explorer i Windows Vista (!).

**Aktivera browser-cache:**
Lägg in nedan i filen .htaccess på web-servern

```
# Set browser cache to x seconds
<IfModule mod_expires.c>
   ExpiresActive On
   ExpiresDefault "now plus 60 minutes"

   # Separate setting for scripts   
   <FilesMatch "\.(pl|php|cgi|spl|scgi|fcgi)$">
       ExpiresDefault "now plus 60 minutes"
   </FilesMatch>

</IfModule>
```

**Cache av localStorage**
Aptus-tavlan verkar ignorera browser-cache. För att få till en cache på tavlan har vi gjort en HTML-sida med ett JavaScript som lagrar resultatet från index.php i localStorage.
För att använda den här cachen, använd sidan indexCached.html
Det går att rensa cache-tiden via en servicemeny på sidan (tryck på klockan)

**Thumbnails**
För att WordPress ska skicka med URL till 'featured image' för varje artikel så har följande lagts till i wp-content/themes/neve/functions.php. När WordPress-temat uppdateras är det risk att denna fil skrivs över, då behöver man lägga till nedan igen (lägg till det längst ner i filen).

```
// Funktion för att lägga till bilder i RSS-flödet för att få kortare laddtider
function rss_post_thumbnail($content) {
global $post;
if(has_post_thumbnail($post->ID)) {
$content = '<postThumbnailUrl src="' . get_the_post_thumbnail_url($post->ID, 'thumbnail' ) . '"></postThumbnailUrl>' . get_the_content();
}
return $content;
}
add_filter('the_excerpt_rss', 'rss_post_thumbnail');
add_filter('the_content_feed', 'rss_post_thumbnail');
// Slut på egen funktion
```
