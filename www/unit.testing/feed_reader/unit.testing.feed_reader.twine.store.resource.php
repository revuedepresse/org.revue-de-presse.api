<?php

if (empty($_GET['p']))

    $parent_id = 'my-items-all/my-items-all';
else

    $parent_id = rawurldecode($_GET['p']);

$identifier = 1;

$pages = 
$items =
$urls = array();

if (preg_match("/\//", $parent_id))

    $par_directories = explode("/", $parent_id);

// set the feed reader class name
$class_feed_reader = CLASS_FEED_READER;

// set a feed reader
$feed_reader = $class_feed_reader::parse("http://## FILL HOSTNAME ##/opml/".$parent_id.".opml");

// set a feed status
$feed_status = FEED_STATUS_ACTIVE;    

// set a feed type
$feed_type = FEED_TYPE_TWINE;

// set the store flag
$store = true;

// set a statement
$insert_contents = "
    INSERT INTO ".TABLE_FEED." (
        fd_contents,
        fd_date_publication,
        fd_hash,
        fd_index,
        fd_parent_id,
        fd_status,
        fd_title,
        fd_type 
    ) VALUES (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )
";

// get a SQL link
$link = sql::getLink();

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

if ($store)

    // loop on pages
    foreach ($pages as $page_id => $page)
//    while (list($page_id, $page) = each($pages))
    {
        $file_name = strg::rewrite($page_id).".html";

        $private_file_path = dirname(__FILE__)."/../../feeds/".$parent_id."/pages/private/".$file_name;
        $public_file_path = dirname(__FILE__)."/../../feeds/".$parent_id."/pages/".$file_name;
    
        $publication_date = substr(
            str_replace(
                array(
                    "T"
                ),
                array(
                    " "
                ),
                $page->published
            ),
            0,
            17
        );
    
        // check the private file
        if (file_exists($public_file_path))
    
            $contents = file_get_contents($public_file_path);
    
        // check the public file
        else if (file_exists($private_file_path))
    
            $contents = file_get_contents($private_file_path);
    
        dumper::log(
            __METHOD__,
            array(
                'private file path',
                $private_file_path,
                'public file path',
                $public_file_path
            ),
            false
        );
    
        $statement = $link->prepare($insert_contents);
    
        // bind parameters to a statement
        $statement->bind_param(
            MYSQLI_STATEMENT_TYPE_STRING.
                MYSQLI_STATEMENT_TYPE_STRING.
                    MYSQLI_STATEMENT_TYPE_STRING.
                        MYSQLI_STATEMENT_TYPE_INTEGER.
                            MYSQLI_STATEMENT_TYPE_STRING.
                                MYSQLI_STATEMENT_TYPE_INTEGER.
                                    MYSQLI_STATEMENT_TYPE_STRING.
                                        MYSQLI_STATEMENT_TYPE_INTEGER,
            $contents,
            $publication_date,
            $page->id,
            $identifier,
            $parent_id,
            $feed_status,
            $page->title,
            $feed_type
        );

        // execute a statement
        $execution_result = $statement->execute();

        // fetch result of a statement
        $fetch_result = $statement->fetch();

        $identifier++;
    }