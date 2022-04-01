<?php

include 'fonctions.php';
include 'fonctionsGraphiques.php';

/*set_error_handler(function ($niveau, $message, $fichier, $ligne) {
	// echo 'Erreur : ' .$message. '<br>';
	// echo 'Niveau de l\'erreur : ' .$niveau. '<br>';
	// echo 'Erreur dans le fichier : ' .$fichier. '<br>';
	// echo 'Emplacement de l\'erreur : ' .$ligne. '<br>';
	if ($niveau == 2) {
		header("Location: accueil.php?erreur=mauvaiseURL");
	}
});*/

/**************************************************************************/
/****************LES USE NECESSAIRE AU FORMALISME GRAPHIQUE****************/
/**************************************************************************/

require_once('src/jpgraph.php');
require_once('src/jpgraph_pie.php');
require_once('src/jpgraph_pie3d.php');
require_once('src/jpgraph.php');
require_once('src/jpgraph_bar.php');
require_once('src/jpgraph_scatter.php');

/******************************************************/
/****************LES VARIABLES GLOBALES****************/
/******************************************************/

//Analyse de l'URL
$nbConsolidation = $_GET['nbconsolidation']; //le nombre de select a faire
$nbConsolidationOriginal = $_GET['nbconsolidation'];
$nbContrainte = $_GET['nbContrainte']; //le nombre de WHERE/GROUP BY/ORDER BY a faire
$nbContrainteOriginal = $_GET['nbContrainte'];
$formalisme = $_GET['formalisme']; //le formalisme souhaité
$nomTable = "total"; //le nom de la table de la base de donnée
$variableJointure = "code";

$tabConsolidation = array(); //contient toutes les recherches du select
$tabConsolidationMod = array(); //contient les modalités de recherche du select
$tabConsolidationOriginal = array();
$tabConsolidationModOriginal = array();
$tabContraintes = array(); //contient toutes les variables soumisent a contrainte
$tabContraintesMod = array(); //contient les modalités de ces contraintes
$tabWhere = array(); //contient la comparaison des comparaison "where"
$tabWhereValeur = array();

//on recupère la connexion à la bd
$link = connexionBase();
//on recupere toutes les consolidations
for ($i = 0; $i < $nbConsolidation; $i++) {
	array_push($tabConsolidation, $_GET['Consolidation' . $i]);
	array_push($tabConsolidationOriginal, $_GET['Consolidation' . $i]);
	if ($_GET['ConsolidationMod' . $i] == "SUM") {
		$text = typeConsolidationBd($_GET['Consolidation' . $i], $link);
		if ($text) {
			array_push($tabConsolidationModOriginal, "COUNT");
			array_push($tabConsolidationMod, "COUNT");
		} else {
			array_push($tabConsolidationModOriginal, "SUM");
			array_push($tabConsolidationMod, "SUM");
		}
	} else {
		array_push($tabConsolidationModOriginal, $_GET['ConsolidationMod' . $i]);
		array_push($tabConsolidationMod, $_GET['ConsolidationMod' . $i]);
	}
}

$possedeUnGroupBy = false;
$insertDeVariableDansSelect = 0;

//on recupere toutes les contraintes
for ($i = 0; $i < $nbContrainte; $i++) {
	array_push($tabContraintes, $_GET['Contrainte' . $i]);
	array_push($tabContraintesMod, $_GET['ContrainteMod' . $i]);
	//si la contrainte est un where
	if ($tabContraintesMod[$i] == "WHERE") {
		$tabWhere[$i] = $_GET['Comparaison' . $i];
		$tabWhereValeur[$i] = $_GET['Valeur' . $i]; //on ajoute au tableau la maniére de comapraison
	} else if ($tabContraintesMod[$i] == "GROUP BY") {
		$possedeUnGroupBy = true;
		$groupByEstDansWhere = false;
		for ($j = 0; $j < $nbConsolidation; $j++) {
			if ($tabConsolidationMod[$j] == " ") {
				$consolidation = $tabConsolidation[$j];
			} else {
				$consolidation = $tabConsolidationMod[$j] . "(" . $tabConsolidation[$j] . ")";
			}

			if ($consolidation == $tabContraintes[$i]) {
				$groupByEstDansWhere = true;
			}
		}
		if (!$groupByEstDansWhere) {
			array_push($tabConsolidation, $tabContraintes[$i]);
			array_push($tabConsolidationMod, " ");
			$nbConsolidation++;
		}
	}
}

//on démarre la séléction
$chaine = "SELECT ";

//on concatène toutes les recherches du select avec leurs modes de recherche et on intègre dans le tableau des GROUP BY les attributs du select qui n'y sont pas
for ($i = 0; $i < $nbConsolidation; $i++) {
	if ($tabConsolidationMod[$i] == " ") {
		$chaine .= "$tabConsolidation[$i]";
	} else {
		$chaine .= "$tabConsolidationMod[$i]($tabConsolidation[$i])";
	}
	if ($i < $nbConsolidation - 1) {
		$chaine .= ",";
	}
}

//on ajoute le from à la requête
$chaine .= " FROM $nomTable ";

//on ajoute tout les WHERE
$nbWhere = 0;

//on parcours le tableau de contraintes
for ($i = 0; $i < $nbContrainte; $i++) {
	//si la contrainte est un WHERE
	if ($tabContraintesMod[$i] == "WHERE") {
		//on la concatène avec son mode de comparaison
		if ($nbWhere == 0) {
			$chaine .= "WHERE $tabContraintes[$i] $tabWhere[$i] \"$tabWhereValeur[$i]\"";
		} else {
			$chaine .= " AND $tabContraintes[$i] $tabWhere[$i] \"$tabWhereValeur[$i]\"" . " ";
		}
		$nbWhere++;
	}
}

//on ajoute tout les GOUP BY
$nbGroupBy = 0;

//on parcours toutes les contraintes
for ($i = 0; $i < $nbContrainte; $i++) {
	//si la contrainte est un group by
	if ($tabContraintesMod[$i] == "GROUP BY") {
		//on la concatène avec sa variable attitrée
		if ($nbGroupBy == 0) {
			$chaine .= " GROUP BY $tabContraintes[$i]";
		} else {
			$chaine .= ",$tabContraintes[$i]";
		}
		$nbGroupBy++;
	}
}

//on ajoute tout les ORDER BY
$nbOrderBy = 0;

//on parcours le tableau
for ($i = 0; $i < $nbContrainte; $i++) {
	//si la contrainte est un order by
	if ($tabContraintesMod[$i] == "ORDER BY") {
		if ($formalisme != "Diagramme en secteur") {
			//on la concatène a la requete avec sa variable attitrée
			if ($nbOrderBy == 0) {
				$chaine .= " ORDER BY $tabContraintes[$i]";
			} else {
				$chaine .= ",$tabContraintes[$i]";
			}
			$nbOrderBy++;
		}
	}
}

$nomDossier = "graphiques/";
$dossier = opendir($nomDossier);

//On parcours le dossier contenant les graphiques et on les supprime
while ($fichier = readdir($dossier)) {
	if ($fichier != "." && $fichier != "..") {

		if (file_exists($nomDossier . $fichier)) {
			unlink($nomDossier . $fichier);
		}
	}
}

switch ($formalisme) {
	case "Diagramme en secteur":
		try {
			DiagrammeSecteur($chaine, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
			header("Location: affichage.php");
		} catch (Exception $e) {
			header("Location: CreerRequete.php?erreur=true");
		}
		break;

	case "Diagramme en barre":
		try {
			DiagrammeBarre($chaine, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
			header("Location: affichage.php");
		} catch (Exception $e) {
			header("Location: CreerRequete.php?erreur=true");
		}
		break;

	case "Nuage de points":
		try {
			NuagePoints($chaine, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
			header("Location: affichage.php");
		} catch (Exception $e) {
			header("Location: CreerRequete.php?erreur=true");
		}
		break;
}
