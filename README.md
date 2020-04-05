Web-program för att visa WordPress-artiklar på en AptusAgera-tavla. Artiklarna hämtas via RSS-flöde i WordPress.


Mått på den del av tavlan som används för den här sidan:
1000 x 1024


Aktivera browser-cache:
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
