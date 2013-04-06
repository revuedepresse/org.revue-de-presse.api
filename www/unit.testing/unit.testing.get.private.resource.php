<?php

if (empty($_GET['p']))

    $parent_id = 'my-items-all/my-items-all';
else

    $parent_id = rawurldecode($_GET['p']);

$pages = 
$items =
$urls = array();

$move = true;

$write = true;

if (preg_match("/\//", $parent_id))

    $par_directories = explode("/", $parent_id);

$class_feed_reader = CLASS_FEED_READER;

$feed_reader = $class_feed_reader::parse("http://## FILL HOSTNAME ##/opml/".$parent_id.".opml");

$dom = $feed_reader->getDOM();

$outlines = $dom->getElementsByTagName('outline');

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

// loop on urls
foreach ($urls as $index => $url)
// while (list($index, $url) = each($urls))
{
    $file_path = dirname(__FILE__)."/../feeds/".$parent_id."/".$index.".xml";
    
    if (file_exists($file_path))
    {
        $contents = file_get_contents($file_path);

        $dom = new DOMDocument;
        $dom->loadXML($contents);

        $entries = $dom->getElementsByTagName('entry');
        
        // loop on outlines
        foreach($entries as $entry)        

            $items[] = $entry;

        while (list($key, $item) = each($items))
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

if ($move)

    foreach ($pages as $page_id => $page)
//    while (list($page_id, $page) = each($pages))
    {
        $contents = null;

        $file_path = dirname(__FILE__)."/../feeds/".$parent_id."/pages/".strg::rewrite($page_id).".html";        
        $dir_path = dirname(__FILE__)."/../feeds/".$parent_id."/pages/404/";
        $dir_path_500 = dirname(__FILE__)."/../feeds/".$parent_id."/pages/500/";
       
    
        // check if the page identifier contains the pattern
/*
        if (preg_match("/1vv5zy0l-vz/", $page_id))
    
            $write = true;
*/
 
        // check the file path
        if (file_exists($file_path))
    
            $contents = file_get_contents($file_path);
    
            if (preg_match("/<title>Page Not Found \(404\) \| Twine<\/title>/", $contents))
            {
                if (!file_exists($dir_path.strg::rewrite($page_id).".html"))
    
                    copy($file_path, $dir_path.strg::rewrite($page_id).".html");
    
                if (file_exists($file_path))
    
                    unlink($file_path);
            }
            else if (preg_match("/<title>Technical Difficulty \| Twine<\/title>/", $contents))
            {
                if (!file_exists($dir_path.strg::rewrite($page_id).".html"))
    
                    copy($file_path, $dir_path_500.strg::rewrite($page_id).".html");
    
                unlink($file_path);            
            }
    }

if ($write)

    foreach ($pages as $page_id => $page)
//    while (list($page_id, $page) = each($pages))
    {
        $contents = null;
    
        $file_path = dirname(__FILE__)."/../feeds/".$parent_id."/pages/404/".strg::rewrite($page_id).".html";
        $private_file_path = dirname(__FILE__)."/../feeds/".$parent_id."/pages/private/".strg::rewrite($page_id).".html";
    
        // check the file path
        if (file_exists($file_path))
        {
            $feed_reader = $class_feed_reader::parse($page->link, false, true);
        
            $_raw_contents = $feed_reader->getRawContents();        
    
            dumper::log(
                __METHOD__,
                array(
                    'page url:',
                    $page->link,
                    'file path',
                    $file_path,
                    'private file path',
                    $private_file_path                    
                ),
                false
            );
    
            if (!file_exists($private_file_path) || file_exists($private_file_path))
            {    
                $handler = fopen($private_file_path, "w+");
                fwrite($handler, $_raw_contents);
                fclose($handler);
            }
        }
    }