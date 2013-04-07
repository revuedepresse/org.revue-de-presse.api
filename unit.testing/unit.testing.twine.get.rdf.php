<?php

if (empty($_GET['p']))

    $parent_id = 'my-items-all/my-items-all';
else

    $parent_id = rawurldecode($_GET['p']);

$duplicated_items = array(
    23,    39,   103,   104,   200,   219,   281,   286,   287,   289,   299,   303,   361,   365,   371,   384,   455,   461,   476,   618,   665,   666,   670,   671,   676,   678,   679,   680,   681,   683,   685,   686,   687,   689,   690,   691,   692,   693,   695,   703,   704,   708,   749,   839,   954,  1013,  1045,  1103,  1104,  1105,  1131,  1352,  1354,  1389,  1548,  1701,  1942,  1974,  2000,  2087,  2323,  2457,  2458,  2519,  2554,  2556,  2561,  2566,  2602,  2932,  3641,  4038,  4059,  4297,  4303,  4305,  4306,  4307,  4312,  4313,  4644,  4645,  4676,  5002,  5199,  5214,  5317,  5318,  5319,  5320,  5410,  5451,  5531,  5811,  5812,  5899,  6009,  6125,  6127,  6184,  6185,  6187,  6191,  6192,  6193,  6206,  6209,  6213,  6214,  6215,  6230,  6231,  6233,  6238,  6239,  6275,  6323,  6359,  6362,  6392,  6393,  6409,  6410,  6431,  6433,  6450,  6452,  6453,  6454,  6466,  6481,  6508,  6527,  6534,  6553,  6648,  6734,  6738,  6759,  6766,  6768,  6769,  6784,  6824,  6827,  6828,  6976,  6977,  7004,  7005,  7061,  7106,  7235,  7237,  7239,  7241,  7242,  7243,  7244,  7247,  7248,  7249,  7321,  7351,  7353,  7355,  7356,  7357,  7388,  7390,  7391,  7397,  7399,  7431,  7530,  7535,  7589,  7603,  7662,  7667,  7672,  7675,  7701,  7721,  7729,  7730,  7731,  7741,  7743,  7746,  7762,  7764,  7776,  7777,  7778,  7779,  7780,  7782,  7795,  7798,  7799,  7802,  7804,  7821,  7829,  7833,  7847,  7849,  7850,  7852,  7853,  7854,  7891,  7892,  7897,  7899,  7900,  7907,  7912,  7983,  8052,  8064,  8116,  8123,  8209,  8210,  8355,  8362,  8370,  8390,  8403,  8563,  8626,  8637,  8642,  8681,  8732,  8733,  8787,  8851,  8867,  8870,  9095,  9103,  9128,  9283,  9398,  9450
);

$pages = 
$urls = array();

$move = false;

$write = false;

if (preg_match("/\//", $parent_id))

    $par_directories = explode("/", $parent_id);

// set a statement
$insert_serialization = "
    INSERT INTO ".TABLE_SERIALIZATION." (
        fd_hash,
        sn_contents,
        sn_uri
    ) VALUES (
        ?,
        ?,
        ?
    )
";

// set a statement
/*
$select_feed = '
    SELECT
        fd_id,
        fd_contents
    FROM
        '.TABLE_FEED.' AS fd
    WHERE
        fd_contents LIKE ?
';
*/

if (empty($_GET['p']))
    
    // set a statement
    $select_feed = '
        SELECT
            fd_id,
            fd_contents
        FROM
            '.TABLE_FEED.' AS fd
        WHERE
            fd_id = ?
    ';
    
else 

    // set a statement
    $select_feed = '
        SELECT
            fd_id,
            fd_contents
        FROM
            '.TABLE_FEED.' AS fd
        WHERE
            fd_index = ? AND
            fd_parent_id = "'.$parent_id.'"
    ';

if (empty($_GET['p']))

    // set a statement
    $update_feed = '
        UPDATE '.TABLE_FEED.' SET 
            sn_uri = ?
        WHERE
            fd_id = ?
    ';

else

    // set a statement
    $update_feed = '
        UPDATE '.TABLE_FEED.' SET 
            sn_uri = ?
        WHERE
            fd_index = ? AND
            fd_parent_id = "'.$parent_id.'"
    ';

// get a SQL link
$link = sql::getLink();

$class_feed_reader = CLASS_FEED_READER;

$feed_reader = $class_feed_reader::parse("http://## FILL HOSTNAME ##/opml/".$parent_id.".opml");

$dom = $feed_reader->getDOM();

$outlines = $dom->getElementsByTagName('outline');

$pattern = '/<link rel="alternate" href="http:\/\/www.## FILL BASE URL ##\/feed\/atom\/entries\/item\/([^"]*)"/';


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

$count = 1;

$handler = fopen(dirname(__FILE__)."/../tmp/log.txt", "a+");

// loop on urls
foreach ($urls as $index => $url)
// while (list($index, $url) = each($urls))
{
    $file_path = dirname(__FILE__)."/../feeds/".$parent_id."/".$index.".xml";

    dumper::log(
        __METHOD__,
        array($file_path),
        false
    );
    
    if (file_exists($file_path))
    {
        $items = array();

        $contents = file_get_contents($file_path);

        $dom = new DOMDocument;
        $dom->loadXML($contents);

        $entries = $dom->getElementsByTagName('entry');
        
        // loop on outlines
        foreach($entries as $entry)        

            $items[] = $entry;

        foreach ($items as $key => $item)
//      while (list($key, $item) = each($items))
        {
            $child_nodes = $item->childNodes;

            foreach ($child_nodes as $child_node)

                if ($child_node->nodeName == 'id')
                {
                    $page = new stdClass();

                    $page->id = $child_node->nodeValue;
                }
                else if ($child_node->nodeName == 'link')
                {
                    // get rdf files
/*
                    $page->link = $child_node->getAttribute('href');
                    
                    $match = preg_match("/item\/([^\/]*)/", $page->link, $matches);

                    $page->item = $matches[1];

                    $page->uri = "http://www.## FILL BASE URL ##/item/".$matches[1]."?rdf";

                    $feed_reader = $class_feed_reader::parse($page->uri, false, true);

                    $page->contents = $feed_reader->getRawContents();
*/
/*
                    fwrite($handler, $page->uri."\n");

                    $statement = $link->prepare($select_feed);

                    $uri = "%http://www.## FILL BASE URL ##/item/".$matches[1]."%";

                    echo $uri;
*/

                    $pages[$page->id] = $page;

/* check duplicated items
                    if (!in_array($count, $duplicated_items))
                    {
*/                        
                        $statement = $link->prepare($select_feed);

                        // bind parameters to a statement
                        $statement->bind_param(
                            MYSQLI_STATEMENT_TYPE_INTEGER,
                            $count
                        );

                        // bind parameters to a statement
                        $statement->bind_result($id, $_contents);

                        // execute a statement
                        $execution_result = $statement->execute();
                    
                        // fetch result of a statement
                        $fetch_result = $statement->fetch();
                    
                        // close the current statement
                        $statement->close();

                        $_match = preg_match($pattern, $_contents, $_matches);

                        if (!isset($_matches[1]))

                            dumper::log(
                                __METHOD__,
                                array(
                                    $count,
                                    $pattern
                                ),
                                false
                            );

                        else
                        {
                            /*
                            dumper::log(
                                __METHOD__,
                                array(
                                    $pattern,
                                    $_matches[1]
                                ),
                                false
                            );
                            */
    
                            $unique_identifier = "http://www.## FILL BASE URL ##/item/".$_matches[1]."?rdf";
    
                            $statement = $link->prepare($update_feed);
    
                            // bind parameters to a statement
                            $statement->bind_param(
                                MYSQLI_STATEMENT_TYPE_STRING.
                                    MYSQLI_STATEMENT_TYPE_INTEGER,
                                $unique_identifier,
                                $count
                            );
    
                            // execute a statement
                            $execution_result = $statement->execute();
                        
                            // fetch result of a statement
                            $fetch_result = $statement->fetch();

                            // close the current statement
                            $statement->close();                            
                        }
/*
                    }
*/
/*
                   if ($fetch_result)
                    
                        $count++;
*/

                    // insert serialization
/* 
                    $statement = $link->prepare($insert_serialization);

                    // bind parameters to a statement
                    $statement->bind_param(
                        MYSQLI_STATEMENT_TYPE_STRING.
                            MYSQLI_STATEMENT_TYPE_STRING.                            
                                MYSQLI_STATEMENT_TYPE_STRING,
                        $page->id,
                        $page->contents,
                        $page->uri
                    );
                
                    // execute a statement
                    $execution_result = $statement->execute();
                
                    // fetch result of a statement
                    $fetch_result = $statement->fetch();
                
                    // close the current statement
                    $statement->close();
*/
                    $count++;
                }
        }
    }    
}

fclose($handler);

echo $count;