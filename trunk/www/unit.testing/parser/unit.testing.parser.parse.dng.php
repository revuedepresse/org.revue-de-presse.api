<?php

$handle = fopen('## FILL ABSOLUTE PATH ##'', 'r');

$image = '';

if ($handle)
{
    $line_number = 1;

    while (!feof($handle) && $line_number != 172)
    {
        $buffer = fgets($handle);

        if ($line_number > 11)

            $image .= $buffer;

        $line_number++;
    }

    fclose($handle);
}

/*
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.strlen($image));
header('Content-type: image/dng');
header("Content-Disposition: attachment; filename=truc.dng;");
*/

echo $image;