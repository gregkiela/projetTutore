<?php

include 'fonctions.php';

$link = connexionBase();

$nbFichier = $_POST['nbFichier'];
$nbURL = $_POST['nbURL'];

$colonneChoisie = $_POST['liste'];

$nbFichier = intval($nbFichier);
$nbURL = intval($nbURL);

$nomTable = "tmp";


$tousLesJSON = array();

//On vérifie qu'on a reçu des fichiers
if ($nbFichier > 0) {
    $fichiers = array();
    //Je vérifie que tous les fichiers sont du bon type
    for ($i = 0; $i < $nbFichier; $i++) {
        $type = $_FILES['fichier']['type'][$i];
        if ($type != "application/vnd.ms-excel" && $type != "application/json" && $type != "application/xml") {
            header("Location: recupDonnees.php?erreur=mauvaisType");
        } else {
            //Si oui on les met dans un tableau
            $tmp = array(
                "tmp_name" => $_FILES['fichier']['tmp_name'][$i],
            );
            array_push($fichiers, $tmp);
        }
    }
    //Pour chaque fichier on recupere son json, son contenu etc
    foreach ($fichiers as $fichier) {
        $reponseDonneeRecu = gestionDonneeRecu($fichier['tmp_name'], true);
        $tmp = array(
            'json' => $reponseDonneeRecu[0],
            'contenu' => $reponseDonneeRecu[1],
            'fichier' => $reponseDonneeRecu[2],
            'nomFichier' => $fichier['tmp_name']
        );
        array_push($tousLesJSON, $tmp);
    }
}

//Même principe pour les urls reçus
if ($nbURL > 0) {
    $urls = array();
    //J'enregistre chaque URL dans un tableau
    for ($i = 0; $i < $nbURL; $i++) {
        $urls[$i] = $_POST['url' . $i];
    }

    foreach ($urls as $url) {
        $reponseDonneeRecu = gestionDonneeRecu($url);
        $tmp = array(
            'json' => $reponseDonneeRecu[0],
            'contenu' => $reponseDonneeRecu[1],
            'fichier' => $reponseDonneeRecu[2]
        );
        array_push($tousLesJSON, $tmp);
    }
}

$nomTables = array();

$i = 0;
//Pour chaque json enregistré
foreach ($tousLesJSON as $donnee) {

    $json = $donnee['json'];
    $contenu = $donnee['contenu'];
    $fichier = $donnee['fichier'];

    //Action différente si c'est un fichier ou non
    if (isset($donnee['nomFichier'])) {
        $json = actionSurJson($json, $contenu, $fichier, $donnee['nomFichier']);
    } else {
        $json = actionSurJson($json, $contenu, $fichier);
    }

    //On récupère plusieurs informations sur les clés présentes dans le json
    $reponseFonction = clesPresentFichier($json);

    //On regarde pour chaque objet du json si les clés sont présentes
    $presenceDesCles = $reponseFonction[0];

    //On récupère les clés
    $cles = $reponseFonction[1];

    //On regarde combien il y a de clés
    $nombreMaxCles = $reponseFonction[2];

    //Ici quel objet (indice) a le plus grand nombre de clés
    $indicePlusGrandsNombreCles = $reponseFonction[3];

    //On vérifie si la table existe, si oui on la supprime
    verifTable($link, $nomTable . $i);

    //Récupération des valeurs de chaque objet json
    $valeursObjets = valeursObjetsJson($json, $presenceDesCles, $nombreMaxCles);

    //On crée la table avec les clés récupérés précedemment
    creationTable($link, $nomTable . $i, $cles, $valeursObjets, $indicePlusGrandsNombreCles);

    //On insère les valeurs récupéré dans la tablé créée
    insertionValeurs($link, $nomTable . $i, $valeursObjets);

    array_push($nomTables, $nomTable . $i);

    $i++;
}

//On crée la requete pour créer une table grace a un join de toute les tables
$requeteJoin = "SELECT * FROM cadca";
foreach ($nomTables as $table) {
    //Pour chaque table on join par le parametre choisi
    $requeteJoin .= " INNER JOIN $table USING($colonneChoisie)";
}

//On verifie la table total
verifTable($link, "total");

//On crée la table
$requete = "CREATE TABLE total AS $requeteJoin";
mysqli_query($link, $requete) or die("impossible");
