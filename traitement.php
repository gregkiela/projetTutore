<?php

include 'fonctions.php';

//On met en place un système d'erreur, si n'importe quelle erreur intervient durant le processus
//on redirige vers la page d'accueil
set_error_handler(function ($niveau, $message, $fichier, $ligne) {
    // echo 'Erreur : ' .$message. '<br>';
    // echo 'Niveau de l\'erreur : ' .$niveau. '<br>';
    // echo 'Erreur dans le fichier : ' .$fichier. '<br>';
    // echo 'Emplacement de l\'erreur : ' .$ligne. '<br>';
    if ($niveau == 2) {
        header("Location: accueil.php?erreur=mauvaiseURL");
    }
});

//Connexion à la base de données
$link = connexionBase();

$nbFichier = $_POST['nbFichier'];
$nbURL = $_POST['nbURL'];

//On récupère le parametre par lequel l'utilisateur souhaite joindre ses données
$colonneChoisie = $_POST['liste'];

//On 
$nbFichier = intval($nbFichier);
$nbURL = intval($nbURL);

$nomTable = "tmp";
$nomTableTotal = "total";


$tousLesJSON = array();

if ($nbFichier > 0) {
    $fichiers = array();
    //Je vérifie que tous les fichiers sont du bon type
    for ($i = 0; $i < $nbFichier; $i++) {
        $type = $_FILES['fichier']['type'][$i];
        if ($type != "application/vnd.ms-excel" && $type != "application/json" && $type != "application/xml") {
            header("Location: AjoutDonnees.php?erreur=mauvaisType");
        } else {
            $tmp = array(
                "tmp_name" => $_FILES['fichier']['tmp_name'][$i],
            );
            array_push($fichiers, $tmp);
        }
    }
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
foreach ($tousLesJSON as $donnee) {

    $json = $donnee['json'];
    $contenu = $donnee['contenu'];
    $fichier = $donnee['fichier'];

    if (isset($donnee['nomFichier'])) {
        $json = actionSurJson($json, $contenu, $fichier, $donnee['nomFichier']);
    } else {
        $json = actionSurJson($json, $contenu, $fichier);
    }

    //En fonction du json créé on effectue diverses actions

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

//Début de la requete permettant de faire le join
$requeteJoin = "SELECT * FROM source";

//Pour chaque table on join
foreach($nomTables as $table)
{
    $requeteJoin.=" INNER JOIN $table USING($colonneChoisie)";
}

//On vérifie la table
verifTable($link,$nomTableTotal);

//On crée la table à partir de la requete
$requete = "CREATE TABLE $nomTableTotal AS $requeteJoin";
mysqli_query($link, $requete) or die(header("Location: accueil.php?erreur=mauvaiseURL"));

header("Location: CreerRequete.php");