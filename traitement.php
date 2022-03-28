<?php

include 'fonctions.php';

$bdd = "gerrecart";
$host = "localhost";
$user = "root";
$pass = "";
$nomTable = "tmp";

//connexion à la base de données
$link = mysqli_connect($host, $user, $pass, $bdd) or die("Impossible de se connecter");

$requete = 'SET NAMES UTF8';
mysqli_query($link, $requete);

$nbFichier = $_POST['nbFichier'];
$nbURL = $_POST['nbURL'];

$colonneChoisie = $_POST['liste'];

$nbFichier = intval($nbFichier);
$nbURL = intval($nbURL);


if ($nbFichier > 0) {
    $fichiers = array();
    //Je vérifie que tous les fichiers sont du bon type
    for ($i = 0; $i < $nbFichier; $i++) {
        $type = $_FILES['fichier']['type'][$i];
        if ($type != "application/vnd.ms-excel" && $type != "application/json") {
            //header("Location: recupDonnees.php?erreur=mauvaisType");
        }
    }
    //J'enregistre les informations de chaque fichiers dans un tableau
    for ($i = 0; $i < $nbFichier; $i++) {
        $tmp = array(
            "name" => $_FILES['fichier']['name'][$i],
            "type" => $_FILES['fichier']['type'][$i],
            "size" => $_FILES['fichier']['size'][$i],
            "tmp_name" => $_FILES['fichier']['tmp_name'][$i],
            "error" => $_FILES['fichier']['error'][$i],
        );
        array_push($fichiers, $tmp);
        foreach ($fichiers as $fichier) {
            $json = file_get_contents($fichier['tmp_name']);
            $json = json_decode($json, true);
            var_dump($json);
        }
    }
}
if ($nbURL > 0) {
    $urls = array();

    //J'enregistre chaque URL dans un tableau
    for ($i = 0; $i < $nbURL; $i++) {
        $urls[$i] = $_POST['url' . $i];
    }

    foreach ($urls as $url) {
        $json = file_get_contents($url);
        $json = json_decode($json, true);
    }
}

//Création de la base avec les libellé des données
verifTable($link, $nomTable);

$xml = false;

if (empty($json)) {
    $formatCSV = strpos($contenuURL, ';');
    if ($formatCSV < 50 && $formatCSV !== false) {
        $json = (csvToJson($urlRecue));
        $json = json_decode($json, true);
    } else {
        $xml = simplexml_load_string($contenuURL);
        $json = json_encode($xml);
        $json = json_decode($json, true);
    }
}

$reponseFonction = clesPresentFichier($json);
$presenceDesCles = $reponseFonction[0];
$cles = $reponseFonction[1];
$nombreMaxCles = $reponseFonction[2];
$indicePlusGrandsNombreCles = $reponseFonction[3];

verifTable($link, $nomTable);

creationTable($link, $nomTable, $cles, $valeursObjets, $indicePlusGrandsNombreCles);

insertionValeurs($link, $nomTable, $valeursObjets);

//Récupérer le type de la colonne
$requete = "SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$nomTable'";
$resultat = mysqli_query($link, $requete) or die("Impossible");

while ($donnees = mysqli_fetch_assoc($resultat)) {
    if ($donnees[('COLUMN_NAME')] == $colonneChoisie) {
        $typeColonne = $donnees[('COLUMN_TYPE')];
    }
}

$nouvelleColonne = $colonneChoisie . '2';

$requeteChangerNomTable = "ALTER TABLE $nomTable CHANGE $colonneChoisie $nouvelleColonne $typeColonne";
mysqli_query($link, $requeteChangerNomTable) or die("Impossible de modifier le nom de la colonne");

$requete = "SELECT * FROM donnees INNER JOIN $nomTable ON donnees.$colonneChoisie = $nomTable.$nouvelleColonne";

mysqli_query($link, $requete) or die("impossible45");


$query = "SHOW TABLES LIKE 'total'";
$result = mysqli_query($link, $query);
$tableExists = mysqli_num_rows($result) > 0;
if ($tableExists) {
    $treuc = "DROP TABLE total";
    mysqli_query($link, $treuc);
}

$requete2 = "CREATE TABLE total AS $requete";

mysqli_query($link, $requete2) or die("impossible");

$requete = "ALTER TABLE total DROP $nouvelleColonne";
mysqli_query($link, $requete) or die("impossible");

?>

<h1>Les tables sont assemblées</h1>