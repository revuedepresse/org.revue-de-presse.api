<?php

// set the dumper class name
$class_dumper = $class_application::getDumperClass();

// set the feed reader class name
$class_feed_reader = $class_application::getFeedReaderClass();

$image_urls =
$fixed_image_urls = 
$url_matches = array();

// set the display flag
$display = false;

// get relative resources
$get_relative_resources = true;

// get images
$get_images = true;

if (empty($_GET['p']))

    exit(); // $feed_parent = 'my-items-all/my-items-all';
else

    $feed_parent = rawurldecode($_GET['p']);

if (empty($_GET['max']))

    exit();
else

    $latest_feed_identifier = $_GET['max'];

// set a feed identifier
$feed_identifier = 1;

$class_dumper::log(
	__METHOD__,
	array($_GET),
	$verbose_mode
);

while ($feed_identifier <= $latest_feed_identifier)
{
    // set a statement
    $select_contents = "
        SELECT
            fd_contents
        FROM
            ".TABLE_FEED."
        WHERE
            fd_index = ? AND
            fd_parent_id = ?
    ";
    
    // set a statement
    $update_contents = "
        UPDATE ".TABLE_FEED." SET
            fd_contents = ?
        WHERE
            fd_index = ? AND
            fd_parent_id = ?
    ";
    
    // get a SQL link
    $link = sql::getLink();
    
    // prepare a statement    
    $statement = $link->prepare($select_contents);
    
    // bind parameters to a statement
    $statement->bind_param(
        MYSQLI_STATEMENT_TYPE_INTEGER.
            MYSQLI_STATEMENT_TYPE_STRING,
        $feed_identifier,
        $feed_parent
    );
    
    // bind parameters to a statement
    $statement->bind_result($contents);
    
    // execute a statement
    $execution_result = $statement->execute();
    
    // fetch result of a statement
    $fetch_result = $statement->fetch();
    
    // close the current statement
    $statement->close();
    
    // set the href ico and css pattern
    $ico_css_pattern = '/href="(?!http(?:s?)|#|<|\/item)([^"]{2,})(\?(?:[a-z]=)?[0-9a-zA-Z]*)"/';
    
    // set the image src attributes
    $image_pattern = '/<img src="(http:\/\/www.## FILL BASE URL ##\/([^"]{2,}))"/';
    
    // set the src and css pattern
    $src_pattern = '/src="(?!http(?:s?)|#|<|\/item)([^"]{2,})(\?(?:[a-z]=)?[0-9a-zA-Z]*)"/';
    
    // set the src pattern
    $url_pattern = '/url\(\s*(?:\.\.)?([^\/]*)?([^\s\)]*)\s*\)/';
    
    dumper::log(
        __METHOD__,
        array(
            'src pattern',
            $src_pattern,
            'ico css pattern',
            $ico_css_pattern,
            
        ),
        false
    );
    
    // check the result
    if ($fetch_result)
    {
        // check the display flag
        if (!$display)
        {    
            if ($get_relative_resources)
            {
                // look for css matches
                $css_match = preg_match_all($ico_css_pattern, $contents, $css_matches);
            
                dumper::log(
                    __METHOD__,
                    array(
                        'css matches:',
                        $css_matches
                    ),
                    false
                );
        
                // check the css matches
                if ($css_match && count($css_matches[1]) != 0)
                {
//                    while (list($index, $occurence) = each($css_matches[1]))
                    foreach ($css_matches[1] as $index => $occurence)
                    {
                        // set a url
                        $url = "http://## FILL BASE URL ##".$occurence.$css_matches[2][$index];
                
                        dumper::log(
                            __METHOD__,
                            array(
                                'url matches',
                                $url
                            ),
                            false
                        );
/*                
                        // parse a URL 
                        $feed_reader = $class_feed_reader::parse($url, false);
                
                        // get the raw contents
                        $_raw_contents = $feed_reader->getRawContents();
*/            
                        $directories = explode("/", $occurence);
                        
                        $directory_path = dirname(__FILE__)."/../";
            
                        $length = count($directories);
            
//                        while (list($dir_index, $directory) = each($directories))
                        foreach ($directories as $dir_index => $directory)
                        {
                            if (!file_exists($directory_path.$directory))
                            {
                                if ($dir_index + 1 < $length)
            
                                    mkdir($directory_path.$directory);
            
                                else
                                {
                                    $without_extension = false;
        
                                    dumper::log(
                                        __METHOD__,
                                        array($directory_path.$directory),
                                        false
                                    );
                                    
                                    if (strlen($directory) > 30)
                                    {
                                        // get a SQL link
                                        $link = sql::getLink();
        
                                        $update_statement = mysqli_stmt_init($link);
        
                                        $original_directory = $directory;
        
                                        $directory = substr($directory, 0 , 29);
        
                                        if ($directories[3] == "css")
        
                                            $contents = str_replace(
                                                "css/".$original_directory.$css_matches[2][$index],
                                                "css/".substr($directory, 0 , 29).".".$directories[3],
                                                $contents
                                            );
        
                                        dumper::log(
                                            __METHOD__,
                                            array(
                                                'old path:',
                                                $original_directory.$css_matches[2][$index],
                                                'new path:',
                                                substr($directory, 0 , 29).".".$directories[3]
                                            ),
                                            false
                                        );
        
                                        // prepare a statement    
                                        $update_statement = $link->prepare($update_contents);
                                        
                                        dumper::log(
                                            __METHOD__,
                                            array($update_contents, $link, $update_statement),
                                            false
                                        );
        
                                        // bind parameters to a statement
                                        $update_statement->bind_param(
                                            MYSQLI_STATEMENT_TYPE_STRING.
                                                MYSQLI_STATEMENT_TYPE_INTEGER.
                                                    MYSQLI_STATEMENT_TYPE_STRING,
                                            $contents,
                                            $feed_identifier,
                                            $feed_parent
                                        );
                                        
                                        // execute a statement
                                        $update_execution_result = $update_statement->execute();
        
                                        $extension = preg_match("/\./", $directory);
        
                                        if (isset($update_execution_result))
        
                                            dumper::log(
                                                __METHOD__,
                                                array($update_execution_result),
                                                false
                                            );
        
                                        $without_extension =
                                            !$extension &&
                                            isset($update_execution_result) &&
                                            $update_execution_result
                                        ;
/*        
                                        $finfo = new finfo(FILEINFO_MIME, "/usr/share/misc/magic.mgc");
            
                                        $file_info = $finfo->buffer($_raw_contents, FILEINFO_MIME);
        
                                        $file_mime_type = $finfo->buffer($_raw_contents, FILEINFO_MIME_TYPE);
        
                                        if ($file_mime_type == "text/x-c")
                                        {
                                            if ($directories[3] == "css")
        
                                                $file_mime_type = "text/css";
        
                                            else if ($directories[3] == "js")
        
                                                $file_mime_type = "text/javascript";                                    
                                        }
      
                                        dumper::log(
                                            __METHOD__,
                                            array(
                                                'file mime type',  
                                                $file_mime_type,
                                                'file info',
                                                $file_info
                                            ),
                                            false
                                        );                               
 */                                  }
/*       
                                    $url_match = preg_match_all($url_pattern, $_raw_contents, $_url_matches);
        
                                    if (count($_url_matches[2]) != 0)
                                    {
                                        $image_urls = array_merge($image_urls, $_url_matches[1]);
        
                                        $url_matches = array_merge($url_matches, $_url_matches[2]);
                                    }
/*        
                                    dumper::log(
                                        __METHOD__,
                                        array(
                                            'url pattern:',
                                            $url_pattern,
                                            'local url matches:',
                                            $_url_matches,
                                            'url matches',
                                            $url_matches
                                        ),
                                        false
                                    );
        
                                    file_put_contents(
                                        $directory_path.$directory.
                                        (
                                            $without_extension
                                        ?
                                            ".".$directories[3]
                                        :
                                            ''
                                        ),
                                        $_raw_contents
                                    );
*/                                }
                            }
        
                            $directory_path = $directory_path.$directory."/";                    
                        }
                    }
                }
        
                // look for src matches
                $src_match = preg_match_all($src_pattern, $contents, $js_matches);
            
                dumper::log(
                    __METHOD__,
                    array(
                        'src matches:',
                        $js_matches
                    ),
                    false
                );
        
                // check the src matches
                if ($src_match && count($js_matches[1]) != 0)
                {
                    foreach ($js_matches[1] as $index => $occurence)
//                    while (list($index, $occurence) = each($js_matches[1]))
                    {
                        // set a url
                        $url = "http://## FILL BASE URL ##".$occurence.$js_matches[2][$index];
                
                        dumper::log(
                            __METHOD__,
                            array($url),
                            false
                        );
/*                
                        // parse a URL
                        $feed_reader = $class_feed_reader::parse($url, false);
                
                        // get the raw contents
                        $_raw_contents = $feed_reader->getRawContents();
*/            
                        $directories = explode("/", $occurence);
                        
                        $directory_path = dirname(__FILE__)."/../";
            
                        $length = count($directories);

                        foreach ($directories as $dir_index => $directory)
//                        while (list($dir_index, $directory) = each($directories))
                        {                
                            if (!file_exists($directory_path.$directory))
                            {
                                if ($dir_index + 1 < $length)
            
                                    mkdir($directory_path.$directory);
            
                                else
                                {
                                    $without_extension = false;
        
                                    dumper::log(
                                        __METHOD__,
                                        array($directory_path.$directory),
                                        false
                                    );
                                    
                                    if (strlen($directory) > 30)
                                    {
                                        // get a SQL link
                                        $link = sql::getLink();
        
                                        $update_statement = mysqli_stmt_init($link);
        
                                        $original_directory = $directory;
        
                                        $directory = substr($directory, 0 , 29);
        
                                        if ($directories[3] == "js")
        
                                            $contents = str_replace(
                                                array(
                                                    "js/".$original_directory.$js_matches[2][$index],
                                                    "<script src=\"http://www.google-analytics.com/urchin.js\" type=\"text/javascript\"></script>",
                                                    "urchinTracker();"
                                                ),
                                                array(
                                                    "js/".substr($directory, 0 , 29).".".$directories[3],
                                                    "",
                                                    ""
                                                ),                                        
                                                $contents
                                            );
        
                                        dumper::log(
                                            __METHOD__,
                                            array(
                                                'old path:',
                                                $original_directory.$js_matches[2][$index],
                                                'new path:',
                                                substr($directory, 0 , 29).".".$directories[3]
                                            ),
                                            false
                                        );
        
                                        // prepare a statement    
                                        $update_statement = $link->prepare($update_contents);
                                        
                                        dumper::log(
                                            __METHOD__,
                                            array($update_contents, $link, $update_statement),
                                            false
                                        );
        
                                        // bind parameters to a statement
                                        $update_statement->bind_param(
                                            MYSQLI_STATEMENT_TYPE_STRING.
                                                MYSQLI_STATEMENT_TYPE_INTEGER.
                                                    MYSQLI_STATEMENT_TYPE_STRING,
                                            $contents,
                                            $feed_identifier,
                                            $feed_parent
                                        );
                                        
                                        // execute a statement
                                        $update_execution_result = $update_statement->execute();
        
                                        $extension = preg_match("/\./", $directory);
        
                                        if (isset($update_execution_result))
        
                                            dumper::log(
                                                __METHOD__,
                                                array($update_execution_result),
                                                false
                                            );
/*        
                                        $without_extension =
                                            !$extension &&
                                            isset($update_execution_result) &&
                                            $update_execution_result
                                        ;
        
                                        // construct a new instance of file information
                                        $finfo = new finfo(FILEINFO_MIME, "/usr/share/misc/magic.mgc");
        
                                        // get the raw contents mime    
                                        $file_info = $finfo->buffer($_raw_contents, FILEINFO_MIME);
        
                                        // get the raw contents mime type
                                        $file_mime_type = $finfo->buffer($_raw_contents, FILEINFO_MIME_TYPE);
        
                                        // check the mime type
                                        if ($file_mime_type == "text/x-c++")
                                        {
                                            if ($directories[3] == "js")
        
                                                $file_mime_type = "text/javascript";
        
                                            else if ($directories[3] == "css")
        
                                                $file_mime_type = "text/css";
                                        }
        
                                        dumper::log(
                                            __METHOD__,
                                            array(
                                                'file mime type',  
                                                $file_mime_type,
                                                'file info',
                                                $file_info
                                            ),
                                            false
                                        );    */                           
                                    }/*
        
                                    file_put_contents(
                                        $directory_path.$directory.
                                        (
                                            $without_extension
                                        ?
                                            ".".$directories[3]
                                        :
                                            ''
                                        ),
                                        $_raw_contents
                                    );
*/                                }
                            }
        
                            $directory_path = $directory_path.$directory."/";                    
                        }
                    }
                }

                foreach ($url_matches as $index => $occurence)
//                while (list($index, $occurence) = each($url_matches))
                {
                    // set a url
                    $url =
                        "http://## FILL BASE URL ##".
                        (
                            !empty($image_urls[$index])
                        ?
                            "/css/".$image_urls[$index]
                        :
                            ""
                        ).$occurence
                    ;
        
                    $fixed_image_urls[] = $url;
/*            
                    // parse a URL
                    $feed_reader = $class_feed_reader::parse($url, false);
            
                    // get the raw contents
                    $_raw_contents = $feed_reader->getRawContents();
        
                    // look for slashes
                    if (empty($image_urls[$index]))
        
                        $directories = explode("/", $occurence);
        
                    else 
        
                        $directories = explode("/", "/css/".$image_urls[$index].$occurence);
        
                    $directory_path = dirname(__FILE__)."/../";
        
                    // get the number of parent directories
                    $length = count($directories);

                    foreach ($directories as $dir_index => $directory)        
//                    while (list($dir_index, $directory) = each($directories))
                    {
                        // check if the current file exists
                        if (!file_exists($directory_path.$directory))
                        {
                            if ($dir_index + 1 < $length)
        
                                mkdir($directory_path.$directory);
        
                            else
                            {
                                dumper::log(
                                    __METHOD__,
                                    array(
                                        'path',
                                        $directory_path.$directory
                                    ),
                                    false
                                );
        
                                file_put_contents(
                                    $directory_path.$directory,
                                    $_raw_contents
                                );
                            }
                        }
        
                        $directory_path = $directory_path.$directory."/";                    
                    }
*/
                }
        
                dumper::log(
                    __METHOD__,
                    array($fixed_image_urls),
                    false
                );
            }
    
            // look for css matches
            $image_match = preg_match_all($image_pattern, $contents, $image_matches);
    
            if ($get_images)
            {
                // check the image matches
                if ($image_match && count($image_matches[1]) != 0)
                {
                    foreach ($image_matches[1] as $index => $occurence)
    //                while (list($index, $occurence) = each($image_matches[1]))
                    {
                        // set a url
                        $url = $occurence;
                
                        dumper::log(
                            __METHOD__,
                            array($url),
                            false
                        );
                
                        // parse a URL
                        $feed_reader = $class_feed_reader::parse($url, false);
                
                        // get the raw contents
                        $_raw_contents = $feed_reader->getRawContents();
            
                        $directories = explode("/", $image_matches[2][$index]);
                        
                        $directory_path = dirname(__FILE__)."/../";
            
                        $length = count($directories);
    
                        foreach ($directories as $dir_index => $directory)        
    //                    while (list($dir_index, $directory) = each($directories))
                        {                
                            if (!file_exists($directory_path.$directory))
                            {
                                if ($dir_index + 1 < $length)
            
                                    mkdir($directory_path.$directory);
            
                                else
                                {
                                    dumper::log(
                                        __METHOD__,
                                        array($directory_path.$directory),
                                        false
                                    );
    
                                    // get a SQL link
                                    $link = sql::getLink();
        
                                    $update_statement = mysqli_stmt_init($link);
        
                                    $contents = str_replace(
                                        array(
                                            $occurence
                                        ),
                                        array(
                                            "/".$image_matches[2][$index]
                                        ),                                        
                                        $contents
                                    );
        
                                    dumper::log(
                                        __METHOD__,
                                        array(
                                            'old path:',
                                            $occurence,
                                            'new path:',
                                            "/".$image_matches[2][$index]
                                        ),
                                        false
                                    );
        
                                    // prepare a statement    
                                    $update_statement = $link->prepare($update_contents);
                                    
                                    dumper::log(
                                        __METHOD__,
                                        array($update_contents, $link, $update_statement),
                                        false
                                    );
        
                                    // bind parameters to a statement
                                    $update_statement->bind_param(
                                        MYSQLI_STATEMENT_TYPE_STRING.
                                            MYSQLI_STATEMENT_TYPE_INTEGER.
                                                MYSQLI_STATEMENT_TYPE_STRING,
                                        $contents,
                                        $feed_identifier,
                                        $feed_parent
                                    );
                                    
                                    // execute a statement
                                    $update_execution_result = $update_statement->execute();
        
                                    $extension = preg_match("/\./", $directory);
        
                                    file_put_contents(
                                        $directory_path.$directory,
                                        $_raw_contents
                                    );
                                }
                            }
                            else if ($dir_index + 1 == $length)
                            {
                                // get a SQL link
                                $link = sql::getLink();
    
                                $update_statement = mysqli_stmt_init($link);
    
                                $contents = str_replace(
                                    array(
                                        $occurence
                                    ),
                                    array(
                                        "/".$image_matches[2][$index]
                                    ),                                        
                                    $contents
                                );
    
                                dumper::log(
                                    __METHOD__,
                                    array(
                                        'old path:',
                                        $occurence,
                                        'new path:',
                                        "/".$image_matches[2][$index]
                                    ),
                                    false
                                );
    
                                // prepare a statement    
                                $update_statement = $link->prepare($update_contents);
                                
                                dumper::log(
                                    __METHOD__,
                                    array($update_contents, $link, $update_statement),
                                    false
                                );
    
                                // bind parameters to a statement
                                $update_statement->bind_param(
                                    MYSQLI_STATEMENT_TYPE_STRING.
                                        MYSQLI_STATEMENT_TYPE_INTEGER.
                                            MYSQLI_STATEMENT_TYPE_STRING,
                                    $contents,
                                    $feed_identifier,
                                    $feed_parent
                                );
    
                                // execute a statement
                                $update_execution_result = $update_statement->execute();                            
                            }
        
                            $directory_path = $directory_path.$directory."/";                    
                        }
                    }
                }
        
                dumper::log(
                    __METHOD__,
                    array(
                        'image pattern',
                        $image_pattern,
                        'image matches:',
                        $image_matches //,
        //                'contents',
        //                $contents
                    ),
                    false
                );
            }
        }
        else
    
            // display the contents
            echo $contents;
    }

    $feed_identifier++;
}