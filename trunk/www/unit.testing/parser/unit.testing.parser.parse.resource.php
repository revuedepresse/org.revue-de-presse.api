<?php

if (empty($_GET['p']))

    $parent_id = 'my-items-all/my-items-all';
else

    $parent_id = rawurldecode($_GET['p']);

$pages = 
$items =
$urls = array();

$write = true;
$write_pages = true;

$class_feed_reader = CLASS_FEED_READER;

$feed_reader = $class_feed_reader::parse("http://## FILL HOSTNAME ##/opml/".$parent_id.".opml");

$dom = $feed_reader->getDOM();

$outlines = $dom->getElementsByTagName('outline');

if (preg_match("/\//", $parent_id))
{
    $par_directories = explode("/", $parent_id);
    
    if (!file_exists(dirname(__FILE__)."/../../feeds/".$par_directories[0]))
    
        mkdir(dirname(__FILE__)."/../../feeds/".$par_directories[0]);

    if (!file_exists(dirname(__FILE__)."/../../feeds/".$par_directories[0]."/".$par_directories[1]))

        mkdir(dirname(__FILE__)."/../../feeds/".$par_directories[0]."/".$par_directories[1]);

    if (!file_exists(dirname(__FILE__)."/../../feeds/".$parent_id."/pages"))
    
        mkdir(dirname(__FILE__)."/../../feeds/".$parent_id."/pages");

    if (!file_exists(dirname(__FILE__)."/../../feeds/".$parent_id."/pages/404"))
    
        mkdir(dirname(__FILE__)."/../../feeds/".$parent_id."/pages/404");

    if (!file_exists(dirname(__FILE__)."/../../feeds/".$parent_id."/pages/500"))
    
        mkdir(dirname(__FILE__)."/../../feeds/".$parent_id."/pages/500");

    if (!file_exists(dirname(__FILE__)."/../../feeds/".$parent_id."/pages/private"))
    
        mkdir(dirname(__FILE__)."/../../feeds/".$parent_id."/pages/private");
}

// http://www.## FILL BASE URL ##/feed/atom/entries/## FILL DIRNAME ##117yyj90m-1gd/photography/items?start=0&amp;amp;sort=alphabetical-a-to-z
// http://www.## FILL BASE URL ##/feed/atom/entries/my-items-all?start=0&amp;sort=alphabetical-a-to-z

// loop on outlines
foreach($outlines as $outline)
{
    $urls[
        str_replace(
            array(
                "http://www.## FILL BASE URL ##/feed/atom/entries/".
                (
                    $par_directories[0] != $par_directories[1]
                ?
                    "## FILL DIRNAME ##".$parent_id."/items"
                :
                    $par_directories[0]
                )
                ."?start=",
                "&sort=alphabetical-a-to-z"
            ),
            array(
                '',
                ''
            ),
            $outline->getAttribute('xmlUrl')
        )
    ] = str_replace(
            "http://",
            "http://shal:## FILL ME ##'@",
            $outline->getAttribute('xmlUrl')
    );
}

dumper::log(
    __METHOD__,
    array(
        'urls:',
        $urls
    ),
    false
);

if ($write)

    // loop on urls
    foreach ($urls as $index => $url)
//    while (list($index, $url) = each($urls))
    {
        $file_path = dirname(__FILE__)."/../../feeds/".$parent_id."/".$index.".xml";
        
        if (!file_exists($file_path) || file_exists($file_path))
        {
            $feed_reader = $class_feed_reader::parse($url);
        
            $_raw_contents = $feed_reader->getRawContents();

                dumper::log(
					__METHOD__,
					array($file_path),
					false
				);

            $handler = fopen($file_path, "w+");
            fwrite($handler, $_raw_contents);
            fclose($handler);
        }
    }

reset($urls);

// loop on urls
foreach ($urls as $index => $url)
// while (list($index, $url) = each($urls))
{
    $file_path = dirname(__FILE__)."/../../feeds/".$parent_id."/".$index.".xml";
    
    if (file_exists($file_path))
    {
        $contents = file_get_contents($file_path);

        $dom = new DOMDocument;
        $dom->loadXML($contents);

        $entries = $dom->getElementsByTagName('entry');
        
        // loop on outlines
        foreach($entries as $entry)        

            $items[] = $entry;

        foreach ($items as $key => $item)
//        while (list($key, $item) = each($items))
        {
            $child_nodes = $item->childNodes;

            foreach ($child_nodes as $child_node)
            {
                if ($child_node->nodeName == 'id')
                {
                    $page = new stdClass();

                    $page->id = $child_node->nodeValue;
                }
                else if ($child_node->nodeName == 'title')

                    $page->title = $child_node->nodeValue;
                
                else if ($child_node->nodeName == 'published')
                
                    $page->published = $child_node->nodeValue;

                else if ($child_node->nodeName == 'link')

                    $page->link = $child_node->getAttribute('href');

                else if ($child_node->nodeName == 'content')
                
                    $pages[$page->published."-".$page->id] = $page;
            }
        }
    }
}

dumper::log(
    __METHOD__,
    array(
        'pages: ',
        $pages
    ),
    false
);

if ($write_pages)

    foreach ($pages as $page_id => $page)
//    while (list($page_id, $page) = each($pages))
    {
        $file_path = dirname(__FILE__)."/../../feeds/".$parent_id."/pages/".strg::rewrite($page_id).".html";
    
        // check if the page identifier contains the pattern
/*        
        if (preg_match("/1vv5zy0l-vz/", $page_id))
    
            $write = true;
*/    

        // check the file path
        if (!file_exists($file_path) || file_exists($file_path))
        {
            $feed_reader = $class_feed_reader::parse($page->link, false);
        
            $_raw_contents = $feed_reader->getRawContents();

            dumper::log(
                __METHOD__,
                array($file_path),
                false
            );

            $handler = fopen($file_path, "w+");
            fwrite($handler, $_raw_contents);
            fclose($handler);
        }
    }