<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='fr'>
    <head>
        <title>{$title}</title>
        <link href='css/sefi_style.css' media="screen" type='text/css' rel='stylesheet' />
        <link href="css/jd.slideshow.css" media="screen" type="text/css" rel="stylesheet" />    
        <script src="js/mootools.release.83.js" type="text/javascript"></script>
        <script src="js/timed.slideshow.js" type="text/javascript"></script>
        <!-- <script src="js/showcase.slideshow.js" type="text/javascript"></script> -->
    </head>
    <body>
        <div id="main-block">
        {literal}
            <script type="text/javascript">
            countArticle = 0;
            var mySlideData = new Array();
            mySlideData[countArticle++] = new Array(
            'snapshots/carte-promo-72-dpi.jpg',
            'article1.html',
            'Item 1 Title',
            'Item 1 Description'
            );
            mySlideData[countArticle++] = new Array(
            'snapshots/pola-5-53pm.jpg',
            'article2.html',
            'Item 2 Title',
            'Item 2 Description'
            );
            mySlideData[countArticle++] = new Array(
            'snapshots/pola-kama-sutra.jpg',
            'article2.html',
            'Item 3 Title',
            'Item 3 Description'
            );
            mySlideData[countArticle++] = new Array(
            'snapshots/pola-even-after-all.jpg',
            'article2.html',
            'Item 4 Title',
            'Item 4 Description'
            );
            mySlideData[countArticle++] = new Array(
            'snapshots/pola-social-club.jpg',
            'article2.html',
            'Item 5 Title',
            'Item 5 Description'
            );
            mySlideData[countArticle++] = new Array(
            'snapshots/pola-sorry-angel.jpg',
            'article2.html',
            'Item 6 Title',
            'Item 6 Description'
            );
            mySlideData[countArticle++] = new Array(
            'snapshots/pola-take-me-to-the-river.jpg',
            'article2.html',
            'Item 7 Title',
            'Item 7 Description'
            );            
            </script>
            <div class="jdSlideshow" id="mySlideshow"></div>
            <script type="text/javascript">
            function startSlideshow() {
            var slideshow = new timedSlideShow($('mySlideshow'), mySlideData);
            }
            addLoadEvent(startSlideshow);
            </script>    
            <!--
            <script type="text/javascript">
            function startSlideshow() {
            var slideshow = new showcaseSlideShow($('mySlideshow'), mySlideData);
            }
            addLoadEvent(startSlideshow);
            </script>
            -->
            </div>
        {/literal}        
        <div id="footer">
        </div>
    </body>
</html>