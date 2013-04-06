<?php
require_once('countries.php');

global $errors;
global $lang;
global $labels;

$index = 0;

$author_fields = array(
    'first_name' => 'Prénom',
    'last_name' => 'Nom',
    'middle_name' => 'Deuxième Prénom',
    'email' => 'Email',
    'day' => 'Jour de naissance (JJ/MM/AAAA)',
    'month' => '/',
    'year' => '/',
    'birth_place' => 'Lieu de naissance',
    'biography' => 'Biographie'
);

$album_settings = array (
    'album_cardinal' => 'Nombre de photos'
);

$photo_fields = array(
    'author' => 'Auteur',
    'title' => 'Titre',
    'keywords' => 'Mots-Clés (séparés par des points-virgules)'
//    'licence' => 'Licence',
);

$location_fields = array(
    'place' => 'Place',
    'city' => 'City',
    'zip_code' => 'Zip Code', 
    'country_code' => 'Country',
    'latitude' => 'Latitude',
    'longitude' => 'Longitude',
    'date' => 'Date',
    'time' => 'Time'
);

$errors = array(
    'author' => 'La valeur de ce champ n\'est pas valide',    
    'keywords' => 'La valeur de ce champ n\'est pas valide',    
    'email' => 'La valeur de ce champ n\'est pas valide',    
    'first_name' => 'La valeur de ce champ n\'est pas valide',
    'last_name' => 'La valeur de ce champ n\'est pas valide',
    'title' => 'La valeur de ce champ n\'est pas valide',    
);

$button_labels = array(
    'selection' => 'Sélectionnez une image',
);

$licence_data = array();

if (isset($fields))
    $fieldsCardinal = count($fields);

$lang = array();
$lang['author_fields'] = array();
$lang['button_labels'] = array();
$lang['location_fields'] = array();
$lang['photo_fields'] = array();
$lang['legend'] = array();
$lang['title'] = array();

$lang['title']['diaporama'] = array();
$lang['title']['diaporama']['vincent_meurin'] = "Diaporama :: Vincent Meurin";
$lang['title']['form_add_album'] = "Création d'un nouvel album";
$lang['title']['form_add_author'] = "Ajout d'un nouvel auteur";
$lang['title']['form_edit_album'] = "Edition d'un album existant";
$lang['title']['form_edit_profile'] = "Edition d'un profil";
$lang['title']['form_upload_photos'] = "Téléchargement des images";
$lang['title']['form_select_album_cardinal'] = "Sélection du nombre d'images";

$lang['title_notes']['form_add_author'] = 'Notes :';
$lang['title_notes']['form_upload_photos'] = 'Notes :';
$lang['title_notes']['form_select_album_cardinal'] = 'Notes :';

$lang['legend']['form_add_album'] = 'Créer un nouvel album';
$lang['legend']['form_add_author'] = 'Ajouter un nouvel auteur';
$lang['legend']['form_edit_album'] = 'Editer un album existant';
$lang['legend']['form_edit_profile'] = 'Editer mon profil';
$lang['legend']['form_upload_photos'] = 'Télécharger des images';
$lang['legend']['form_select_album_cardinal'] = "Sélectionner le nombre d'images à télécharger";

$lang['notes']['form_add_author'] = 'Les champs obligatoires sont étoilés';
$lang['notes']['form_upload_photos'] = 'Tous les champs sont obligatoires';
$lang['notes']['form_select_album_cardinal'] = "Le nombre maximum de photos téléchargeable par album d'envoi est de 10";

$lang['acknowledgment']['add_author'] = 'Un nouvel auteur a été ajouté avec succès.';
$lang['acknowledgment']['upload_photos'] = 'Une ou plusieurs photos ont été ajoutées avec succès.';

foreach ($author_fields as $author_field_id => $author_field_value) {
    $lang['author_fields'][$author_field_id] = array();

    $lang['author_fields'][$author_field_id]['label_value'] = $author_field_value;
    
    if ($author_field_id == 'biography')
        $lang['author_fields'][$author_field_id]['type'] = 'textarea';
    else
        $lang['author_fields'][$author_field_id]['type'] = 'text';
        
    if ($author_field_id == 'month' || $author_field_id == 'day')
        $lang['author_fields'][$author_field_id]['max_char'] = 2;

    if ($author_field_id == 'year')
        $lang['author_fields'][$author_field_id]['max_char'] = 4;
        
    $lang['author_fields'][$author_field_id]['is_input'] = true;    

    if ($author_field_id != 'birtday' &&
        $author_field_id != 'birth_location' &&
        $author_field_id != 'email'
    ) {
        $lang['author_fields'][$author_field_id]['hidden'] = false;
        $index++;
    } else 
        $lang['author_fields'][$author_field_id]['hidden'] = true;
        
    if ($author_field_id == 'first_name' ||
        $author_field_id == 'last_name' ||
        $author_field_id == 'email') {
        $lang['author_fields'][$author_field_id]['is_mandatory'] = true;
    }
        
    $lang['author_fields'][$author_field_id]['evenIndex'] = $index % 2 == 1 ?  true : false;
}

foreach ($location_fields as $location_field_id => $location_field_value) {
    $lang['location_fields'][$location_field_id] = array();
    $lang['location_fields'][$location_field_id]['label_value'] = $location_field_value;
    $lang['location_fields'][$location_field_id]['is_input'] = true;    

    if ($location_field_id != 'size' &&
        $location_field_id != 'height' &&
        $location_field_id != 'weight'
    ) {
        $lang['location_fields'][$location_field_id]['hidden'] = false;
        $index++;
    }
    else 
        $lang['location_fields'][$location_field_id]['hidden'] = true;    

    $lang['location_fields'][$location_field_id]['evenIndex'] = $index % 2 == 1 ?  true : false;
}

foreach ($photo_fields as $photo_field_id => $photo_field_value) {
    $lang['photo_fields'][$photo_field_id] = array();
    $lang['photo_fields'][$photo_field_id]['label_value'] = $photo_field_value;
    $lang['photo_fields'][$photo_field_id]['is_input'] = true;    

    if ($photo_field_id != 'latitude' &&
        $photo_field_id != 'longitute' &&        
        $photo_field_id != 'date' &&
        $photo_field_id != 'time' &&
        $photo_field_id != 'zip_code'
    ) {
        $lang['photo_fields'][$photo_field_id]['hidden'] = false;
        $index++;
    }
    else 
        $lang['photo_fields'][$photo_field_id]['hidden'] = true;

    $lang['photo_fields'][$photo_field_id]['evenIndex'] = $index % 2 == 1 ?  true : false;
}

foreach ($album_settings as $album_setting_id => $album_setting) {
    if ($album_setting_id == 'album_cardinal') {
        $lang['album_settings'][$album_setting_id] = array();
        $lang['album_settings'][$album_setting_id]['name'] = $album_setting_id;
        $lang['album_settings'][$album_setting_id]['label_value'] = $album_setting;
        $lang['album_settings'][$album_setting_id]['node_type'] = 'select';
        
        $lang['album_settings'][$album_setting_id]['options'] = array();

        $cardinal = 1;
        
        while ($cardinal < 11) {
            $lang['album_settings'][$album_setting_id]['options'][(int)$cardinal] = array();
            
            $lang['album_settings'][$album_setting_id]['options'][(int)$cardinal]['humanly_readable'] = $cardinal;
            $lang['album_settings'][$album_setting_id]['options'][(int)$cardinal]['value'] = $cardinal;

            $cardinal++;
        }
    }
}

foreach ($button_labels as $button_label_id => $button_label_value) {
    $lang['button_labels'][$button_label_id] = array();
    $lang['button_labels'][$button_label_id]['label_value'] = $button_label_value;
}
?>