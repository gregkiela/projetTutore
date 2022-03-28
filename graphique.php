<?php

/**************************************************************************/
/****************LES USE NECESSAIRE AU FORMALISME GRAPHIQUE****************/
/**************************************************************************/

require_once('src/jpgraph.php');
require_once('src/jpgraph_pie.php');
require_once('src/jpgraph_pie3d.php');
require_once('src/jpgraph.php');
require_once('src/jpgraph_bar.php');

/******************************************************/
/****************LES VARIABLES GLOBALES****************/
/******************************************************/

//Analyse de l'URL
$nbConsolidation = $_GET['nbconsolidation'];
$nbContrainte = $_GET['nbContrainte'];
$region = $_GET['region'];
$formalisme = $_GET['formalisme'];

$tabConsolidation= array();
$tabConsolidationMod = array();
$tabContraintes= array();
$tabContraintesMod= array();
$tabWhere=array();
for($i=0;$i<$nbConsolidation;$i++)
{
	array_push($tabConsolidation,$_GET['Consolidation'.$i]);
	array_push($tabConsolidationMod,$_GET['ConsolidationMod'.$i]);
}

for($i=0;$i<$nbContrainte;$i++)
{
	array_push($tabContraintes,$_GET['Contrainte'.$i]);
	array_push($tabContraintesMod,$_GET['ContrainteMod'.$i]);
	if($tabContraintesMod[$i]=="WHERE")
	{
		$tabWhere[$i]=$_GET['Comparaison'.$i].$_GET['Valeur'.$i];
	}
}

var_dump($tabConsolidation);
var_dump($tabConsolidationMod);
var_dump($tabContraintes);
var_dump($tabContraintesMod);
var_dump($tabWhere);

/*
switch ($formalisme) {
	case "Diagramme en secteur":
		DiagrammeSecteur($parametre1, $parametre2, $parametre3,$typeDonnees);
		break;

	case "Diagramme en barre":
		DiagrammeBarre($parametre1, $parametre2, $parametre3,$typeDonnees);
		break;

	default:
		echo "Pas de fonction associé a ce choix";
		break;
}*/

/********************************************************/
/****************LES FONCTIONS DE GRAPHES****************/
/********************************************************/

function DiagrammeBarre($parametre1, $parametre2, $parametre3, $typeDonnees)
{
	//appel de la fonction de connexion à la base de donnée et on recupere les parametres voulus
	$link = connexion_Base();
	$par1 = $parametre1;
	$par2 = $parametre2;
	$par3 = $parametre3;
	$type=$typeDonnees;
	
	//requete recuperant les valeurs de la base de données
	$nomTable = "Departements";

if($type=="int-int" || $type=="int-varchar")
{
	$reponse = "SELECT $par1,$par2 FROM $nomTable WHERE region='$par3' ORDER BY $par2";
	$result = mysqli_query($link, $reponse) or die("selection impossible 1");

	if ($par3 == "region") {
		$reponse = "SELECT SUM($par1),$par3 FROM $nomTable GROUP BY $par3";
		$result = mysqli_query($link, $reponse) or die("selection impossible 2");
	}

	// Definir les données
	$dataPar1 = array();
	$dataPar2 = array();

	while ($donnees = mysqli_fetch_assoc($result)) {
		if ($par3 == "region") {
			array_push($dataPar1, $donnees["SUM($par1)"]);
			array_push($dataPar2, $donnees["$par3"]);
		} else {
			array_push($dataPar1, $donnees["$par1"]);
			array_push($dataPar2, $donnees["$par2"]);
		}
	}
}
else if($type=="varchar-varchar")
{
	$reponse = "SELECT $par1,$par2 FROM $nomTable WHERE region='$par3' ORDER BY $par2";
	$result = mysqli_query($link, $reponse) or die("selection impossible 1");

	if ($par3 == "region") {
		$reponse = "SELECT COUNT($par1),$par3 FROM $nomTable GROUP BY $par3";
		$result = mysqli_query($link, $reponse) or die("selection impossible 2");
	}

	// Definir les données
	$dataPar1 = array();
	$dataPar2 = array();

	while ($donnees = mysqli_fetch_assoc($result)) {
		if ($par3 == "region") {
			array_push($dataPar1, $donnees["COUNT($par1)"]);
			array_push($dataPar2, $donnees["$par3"]);
		} else {
			array_push($dataPar1, $donnees["$par1"]);
			array_push($dataPar2, $donnees["$par2"]);
		}
	}
}
	// Créer le graphe
	$graph = new Graph(2500, 800, 'auto');
	$graph->SetScale("textlin");
	$graph->SetShadow();
	$graph->SetMargin(60, 60, 60, 60);

	//On définit les axes
	$graph->xaxis->SetTickLabels($dataPar2);
	$graph->xaxis->title->Set(ucfirst($par2));
	$graph->yaxis->title->Set(ucfirst($par1));

	//Définir un titre pour le graphe
	$graph->title->Set(ucfirst($par1)." en fonction du ".ucfirst($par2)." dans la region ".$par3);

	//Modéliser le graphe
	$bplot = new BarPlot($dataPar1);
	$graph->Add($bplot);

	//Afficher
	$graph->Stroke();
}

function DiagrammeSecteur($parametre1, $parametre2, $parametre3, $typeDonnees)
{
	//appel de la fonction de connexion à la base de donnée et on recupere les deux parametres voulues
	$link = connexion_Base();
	$par1 = $parametre1;
	$par2 = $parametre2;
	$par3 = $parametre3;
	$type = $typeDonnees;
	
	//requete recuperant les valeurs de la base de données
	$nomTable = "Departements";

//ici on fait un if afin de faire une selection de données adapter celon le type des données
if($type=="int-varchar" || $type=="int-int")
{
	$reponse = "SELECT $par1,$par2 FROM $nomTable WHERE region='$par3' ORDER BY $par1 ASC";
	$result = mysqli_query($link, $reponse) or die("selection impossible");

	if ($par3 == "region") {
		$reponse = "SELECT SUM($par1),$par3 FROM $nomTable GROUP BY $par3 ORDER BY SUM($par1) ASC";
		$result = mysqli_query($link, $reponse) or die("selection impossible");
	}

	// Definir les données
	$dataPar1 = array();
	$dataPar2 = array();

	while ($donnees = mysqli_fetch_assoc($result)) {
		if ($par3 == "region") {
			array_push($dataPar1, $donnees["SUM($par1)"]);
			array_push($dataPar2, $donnees["$par3"]);
		} else {
			array_push($dataPar1, $donnees["$par1"]);
			array_push($dataPar2, $donnees["$par2"]);
		}
	}
}

	// Créer le graphe
	$graph = new PieGraph(1500, 800);
	$graph->SetShadow();

	// Definir un titre pour le graphe
	$graph->title->Set(ucfirst($par1)." en fonction de ".ucfirst($par2)." dans la region ".$par3);
	// Modeliser le graphe
	$p1 = new PiePlot($dataPar1);
	//$p1->SetLabelType(PIE_VALUE_ADJPERCENTAGE);
	$p1->SetStartAngle(90);
	$p1->SetLegends($dataPar2);

	$p1->SetLabelPos(0.7);

	//On précise la taille
	$p1->SetSize(0.25);

	$p1->SetGuideLines(true,false);
	$p1->SetGuideLinesAdjust(1.1);

	$graph->Add($p1);
	$graph->Stroke();
	
}

/********************************************************/
/****************LES FONCTIONS ANNEXES****************/
/********************************************************/

/**FONCTION DE CONNEXION A LA BASE DE DONNEES**/
function connexion_Base()
{

	//paramétres de la base de données
	$bdd = "glavergne001_pro";
	$host = "lakartxela.iutbayonne.univ-pau.fr";
	$user = "glavergne001_pro";
	$pass = "glavergne001_pro";

	//connexion à la base de données
	$link = mysqli_connect($host, $user, $pass, $bdd) or die("impossible de se connecter");

	//retourner le lien à la base de données
	return $link;
}

