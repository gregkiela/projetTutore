<?php

include 'fonctions.php';

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

//on recupere toutes les consolidations
for ($i = 0; $i < $nbConsolidation; $i++) {
	array_push($tabConsolidation, $_GET['Consolidation' . $i]);
	array_push($tabConsolidationMod, $_GET['ConsolidationMod' . $i]);
	array_push($tabConsolidationOriginal, $_GET['Consolidation' . $i]);
	array_push($tabConsolidationModOriginal, $_GET['ConsolidationMod' . $i]);
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
			if ($tabConsolidation[$j] == $tabContraintes[$i]) {
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

//on concaténe toutes les recherches du select avec leurs modes de recherche et on intégre dans le tableau des GROUP BY les attributs du select qui n'y sont pas
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



//on ajoute le from à la requete
$chaine .= " FROM $nomTable ";

//on ajoute tout les where
$nbWhere = 0;

//on parcours le tableau de contraintes
for ($i = 0; $i < $nbContrainte; $i++) {
	//si la contrainte est un where
	if ($tabContraintesMod[$i] == "WHERE") {
		//on la concaténe avec son mode de comparaison
		if ($nbWhere == 0) {
			$chaine .= "WHERE $tabContraintes[$i] $tabWhere[$i] \"$tabWhereValeur[$i]\"";
		} else {
			$chaine .= " AND $tabContraintes[$i] $tabWhere[$i] \"$tabWhereValeur[$i]\"" . " ";
		}
		$nbWhere++;
	}
}

//on ajoute tout les group by
$nbGroupBy = 0;

//on parcours toutes les contraintes
for ($i = 0; $i < $nbContrainte; $i++) {
	//si la contrainte est un group by
	if ($tabContraintesMod[$i] == "GROUP BY") {
		//on la concaténe avec sa variable attitrée
		if ($nbGroupBy == 0) {
			$chaine .= " GROUP BY $tabContraintes[$i]";
		} else {
			$chaine .= ",$tabContraintes[$i]";
		}
		$nbGroupBy++;
	}
}

//on ajoute tout les orders by
$nbOrderBy = 0;

//on parcours le tableau
for ($i = 0; $i < $nbContrainte; $i++) {
	//si la contrainte est un order by
	if ($tabContraintesMod[$i] == "ORDER BY") {
		//on la concaténe a la requete avec sa variable attitrée
		if ($nbOrderBy == 0) {
			$chaine .= " ORDER BY $tabContraintes[$i]";
		} else {
			$chaine .= ",$tabContraintes[$i]";
		}
		$nbOrderBy++;
	}
}

$nomDossier = "graphiques/";
$dossier = opendir($nomDossier);


while ($fichier = readdir($dossier)) {
	if ($fichier != "." && $fichier != "..") {

		if (file_exists($nomDossier . $fichier)) {
			unlink($nomDossier . $fichier);
		}
	}
}

switch ($formalisme) {
	case "Diagramme en secteur":
		DiagrammeSecteur($chaine, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
		break;

	case "Diagramme en barre":
		DiagrammeBarre($chaine, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
		break;

	case "Nuage de points":
		NuagePoints($chaine, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
		break;

	default:
		echo "Pas de fonction associé a ce choix";
		break;
}

//header("Location: affichage.php");

/********************************************************/
/****************LES FONCTIONS DE GRAPHES****************/
/********************************************************/

function DiagrammeBarre($requete, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
	//echo $requete;
	//appel de la fonction de connexion à la base de donnée et on recupere les parametres voulus
	$link = connexionBase();
	$result = mysqli_query($link, $requete) or die("selection impossible 2");

	// Definir les données
	$dataPar2 = array(); //Nom des colonnes en axe X
	$tabArray = array(); //Contient toutes les valeurs de toutes les consolidations de la requete

	//permet de remplir le tableau 
	$tabArray = renvoieValeurSelect($result, $tabConsolidationModOriginal, $tabConsolidationOriginal, $nbConsolidationOriginal);

	$result = mysqli_query($link, $requete) or die("selection impossible 2");

	for ($i = 0; $i < $nbContrainte; $i++) {
		if ($tabContraintesMod[$i] == "GROUP BY") {
			while ($donnees = mysqli_fetch_assoc($result)) {
				array_push($dataPar2, $donnees["$tabContraintes[$i]"]);
			}
		}
	};

	// Créer le graphe
	$largeurGraphique = $nbConsolidationOriginal * 550;
	$hauteurGraphique = $nbConsolidationOriginal * 325;

	$graph = new Graph($largeurGraphique, $hauteurGraphique, 'auto');
	$graph->SetScale("textlin");

	$graph->graph_theme = null;

	$graph->SetMargin(60, 40, 40, 60);
	$graph->SetBox(true);

	//Axe des abcisses
	$graph->xaxis->SetTickLabels($dataPar2);
	$graph->xaxis->title->Set(ucfirst($tabContraintes[0]));

	//Axe des ordonnées
	$graph->yaxis->HideTicks(true, false);
	//graph->yaxis->title->Set();

	$groupeDePlot = array();

	$text = "";
	// Create the bar plots
	for ($i = 0; $i < count($tabConsolidationOriginal); $i++) {
		$text .= agregationToText($tabConsolidationModOriginal[$i]) . $tabConsolidationOriginal[$i];
		if ($i != count($tabConsolidationOriginal) - 1) {
			$text .= " et ";
		}
		$bplot = new BarPlot($tabArray[$i]);
		$bplot->SetColor("white");
		$bplot->SetFillColor(GetColorList()[$i]);
		$bplot->value->Show();
		$bplot->value->SetColor(GetColorList()[$i]);
		$bplot->value->SetFormat('%01.0f');
		array_push($groupeDePlot, $bplot);
	}
	$groupeDePlot = new GroupBarPlot($groupeDePlot);

	$graph->Add($groupeDePlot);

	$text .= " par " . $tabContraintes[0];

	$graph->title->Set($text);

	$fileName = "graphiques/imagefile.png";
	$graph->Stroke($fileName);
}

function DiagrammeSecteur($requete, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
	//appel de la fonction de connexion à la base de donnée et on recupere les deux parametres voulues
	$link = connexionBase();

	// Create the plots
	for ($i = 0; $i < $nbConsolidationOriginal; ++$i) {
		$graph = new PieGraph(1200, 800);
		$graph->SetShadow();
		// $theme_class = new UniversalTheme;
		// $graph->SetTheme($theme_class);
		//$graph->graph_theme = null;

		//Création du titre
		//var_dump($tabConsolidationModOriginal);
		$text = agregationToText($tabConsolidationModOriginal[$i]);
		$titre = $text . $tabConsolidationOriginal[$i];
		$graph->title->Set($titre);

		//$graph->SetMargin(60, 40, 10, 40);

		$dataPar2 = array(); //Nom des colonnes en axe X
		$tabArray = array(); //Contient toutes les valeurs de toutes les consolidations de la requete

		$requeteCourante = '';
		for ($j = 0; $j < $nbContrainte; $j++) {
			if ($tabContraintesMod[$j] == "GROUP BY") {
				$requeteCourante = $requete . " ORDER BY $tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])";
				$result = mysqli_query($link, $requeteCourante) or die("selection impossible 2");
				while ($donnees = mysqli_fetch_assoc($result)) {
					array_push($dataPar2, $donnees["$tabContraintes[$j]"]);
				}
			}
		}

		//var_dump($dataPar2);

		$result = mysqli_query($link, $requeteCourante) or die("selection impossible 2");

		while ($donnees = mysqli_fetch_assoc($result)) {
			array_push($tabArray, $donnees["$tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])"]);
		}

		$p = new PiePlot($tabArray);

		$p->SetStartAngle(90);

		//$p->SetMargin(0,0,0,0);

		//$p->value->Show();

		//$p->SetSize(1);

		//$p->SetCenter(0.5, 0.45);

		// Use one legend for the whole graph
		$p->SetLegends($dataPar2);
		//$graph->legend->SetShadow(false);
		$graph->Add($p);

		$fileName = "graphiques/imagefile$i.png";
		// $graph->Stroke($fileName);
		$graph->Stroke();
	}
}

function NuagePoints($chaine, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
	//appel de la fonction de connexion à la base de donnée et on recupere les parametres voulus
	$link = connexionBase();


	$result = mysqli_query($link, $chaine) or die("selection impossible 2");

	// Definir les données
	$dataPar2 = array(); //Nom des colonnes en axe X
	$tabArray = array(); //Contient toutes les valeurs de toutes les consolidations de la requete


	//permet de remplir le tableau 

	$tabArray = renvoieValeurSelect($result, $tabConsolidationModOriginal, $tabConsolidationOriginal, $nbConsolidationOriginal);


	$result = mysqli_query($link, $chaine) or die("selection impossible 2");

	for ($i = 0; $i < $nbContrainte; $i++) {
		if ($tabContraintesMod[$i] == "GROUP BY") {
			while ($donnees = mysqli_fetch_assoc($result)) {
				array_push($dataPar2, $donnees["$tabContraintes[$i]"]);
			}
		}
	};

	// Créer le graphe
	$largeurGraphique = $nbConsolidationOriginal * 1500;
	$hauteurGraphique = $nbConsolidationOriginal * 700;


	$graph = new Graph($largeurGraphique, $hauteurGraphique);
	$graph->SetScale("linlin");

	$graph->img->SetMargin(40, 40, 40, 40);
	$graph->SetShadow();

	$graph->title->Set("A simple scatter plot");
	$graph->title->SetFont(FF_FONT1, FS_BOLD);

	// Create the bar plots
	for ($i = 0; $i < count($tabConsolidationOriginal); $i++) {
		$tabx = array();
		for ($j = 0; $j < count($tabArray[$i]); $j++) {
			array_push($tabx, $tabArray[$i][$j]);
		}
		$tabFinal = new ScatterPlot($tabx, $dataPar2);
		$tabFinal->link->Show();
		$tabFinal->link->SetColor(GetColorList()[$i]);
		// ...and add it to the graPH
		$graph->Add($tabFinal);
	}


	$graph->title->Set("Scatter plot");

	// Display the graph
	$fileName = "graphiques/imagefile.png";
	//$graph->Stroke($fileName);
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

function renvoieValeurSelect($requete, $tabConsolidationModOriginal, $tabConsolidationOriginal, $nbConsolidationOriginal)
{
	$nbElement = 0;
	$tabArray = array();

	while ($donnees = mysqli_fetch_assoc($requete)) {
		for ($i = 0; $i < $nbConsolidationOriginal; $i++) {
			$tabArray[$i][$nbElement] = $donnees["$tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])"];
		}
		$nbElement++;
	}

	return ($tabArray);
}

function agregationToText($agregation)
{
	$text = "";
	switch ($agregation) {
		case 'SUM':
			$text = "Le nombre de ";
			break;
		case 'AVG':
			$text = "La moyenne de ";
			break;
		case 'COUNT':
			$text = "Evocation de ";
			break;
	}
	return $text;
}

function GetColorList()
{
	return array(
		'#61a9f3', #blue
		'#f381b9', #red
		'#61E3A9', #green

		#'#D56DE2',
		'#85eD82',
		'#F7b7b7',
		'#CFDF49',
		'#88d8f2',
		'#07AF7B',
		'#B9E3F9',
		'#FFF3AD',
		'#EF606A',
		'#EC8833',
		'#FFF100',
		'#87C9A5',
	);
}
