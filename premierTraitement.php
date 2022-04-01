<?php

include 'fonctions.php';

session_start();

//On se connecte à la base
$link = connexionBase();

$nomTable = "source";

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

//Si on reçoit des fichiers on vérifie leur type
if (!empty($_FILES)) {
    $type = $_FILES['nom']['type'];
    if ($type != "application/vnd.ms-excel" && $type != "application/json" && $type != "application/xml") {
        //Si pas respecté on redirige vers l'accueil
        header("Location: Accueil.php?erreur=mauvaisType");
    }
    $reponseDonneeRecu = gestionDonneeRecu($_FILES['nom']['tmp_name'], true);
//Si on reçoit pas de fichier (donc une URL)
} else {
    $reponseDonneeRecu = gestionDonneeRecu($_POST['nom']);
}

//On récupère le json associé
$json = $reponseDonneeRecu[0];

//Et le contenu de la donnée envoyé par le formulaire
$contenu = $reponseDonneeRecu[1];

//Savoir si c'est un fichier
$fichier = $reponseDonneeRecu[2];

//En fonction du json créé on effectue diverses actions
$json = actionSurJson($json, $contenu, $fichier);

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
verifTable($link, $nomTable);

//Récupération des valeurs de chaque objet json
$valeursObjets = valeursObjetsJson($json, $presenceDesCles, $nombreMaxCles);

//On crée la table avec les clés récupérés précedemment
creationTable($link, $nomTable, $cles, $valeursObjets, $indicePlusGrandsNombreCles);

//On insère les valeurs récupéré dans la tablé créée
insertionValeurs($link, $nomTable, $valeursObjets);

//On récupère le nom des colonnes de la table
$stockNomColonnes = recupNomColonnes($link, $nomTable);

//On stock ces noms de colonnes dans une variable de session (utile plus tard)
$_SESSION['nomColonnes'] = $stockNomColonnes;

//On redirige vers la page suivante
header("Location: AjoutDonnees.php");
