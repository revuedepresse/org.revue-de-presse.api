<?php

define("RDFAPI_INCLUDE_DIR","rdfapi-php/api/");
include(RDFAPI_INCLUDE_DIR."RdfAPI.php");

$someDoc = new Resource("http://www.example.org/myVocabulary/title");
$statement1 = new Statement($someDoc, new Resource("http://www.example.org/myVocabulary/title"), new Literal("RAP tutorial"));
$model1 = ModelFactory::getDefaultModel();
$model1->add($statement1); 
$model2 = ModelFactory::getDefaultModel();
$model2->add(new Statement($someDoc, new Resource("http://www.example.org/myVocabulary/title"), new Literal("RAP tutorial")));
$model2->add(new Statement($someDoc, new Resource("http://www.example.org/myVocabulary/language"), new Literal("English")));

echo "\$model1 contains " .$model1->size() ." statements";

// Output $model1 as HTML table
echo "<b>Output the MemModel as HTML table: </b><p>";
$model1->writeAsHtmlTable();

// Output the string serialization of $model1
echo "<b>Output the plain text serialization of the MemModel: </b><p>";
echo $model1->toStringIncludingTriples();

// Output the RDF/XML serialization of $model1
echo "<b>Output the RDF/XML serialization of the MemModel: </b><p>";
echo $model1->writeAsHtml();