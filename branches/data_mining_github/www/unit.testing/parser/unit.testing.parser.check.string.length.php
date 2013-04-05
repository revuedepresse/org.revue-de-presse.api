<?php

$string = 'Create a sc2_form to manage Client Users and Herport users.



Add cli_id field in sc2_collaborateur table.



Herport Users : ( Xnnn )

- Company ( global, all clients )

- Forwarders ( attached to some ports )

- 0 in sc2_collaborateur.cli_id



SC-2 us';

echo 'The measured length for the provided string is: '.strlen( $string ).'.';