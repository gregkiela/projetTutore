<?php

/**************************************************************************/
/****************LES USE NECESSAIRE AU FORMALISME GRAPHIQUE****************/
/**************************************************************************/

require_once('src/jpgraph.php');
require_once('src/jpgraph_pie.php');
require_once('src/jpgraph_pie3d.php');
require_once('src/jpgraph.php');
require_once('src/jpgraph_bar.php');
require_once ('src/jpgraph_scatter.php');

/******************************************************/
/****************LES VARIABLES GLOBALES****************/
/******************************************************/

//Analyse de l'URL
$nbConsolidation = $_GET['nbconsolidation']; //le nombre de select a faire
$nbConsolidationOriginal = $_GET['nbconsolidation'];
$nbContrainte = $_GET['nbContrainte']; //le nombre de WHERE/GROUP BY/ORDER BY a faire
$nbContrainteOriginal = $_GET['nbContrainte'];
$formalisme = $_GET['formalisme']; //le formalisme souhaité
$nomTable = "Departements"; //le nom de la table de la base de donnée
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

switch ($formalisme) {
	case "Diagramme en secteur":
		DiagrammeSecteur($chaine, $tabConsolidation, $tabConsolidationMod, $nbConsolidation, $tabContraintes, $tabContraintesMod, $tabWhere, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
		break;

	case "Diagramme en barre":
		DiagrammeBarre($chaine, $tabConsolidation, $tabConsolidationMod, $nbConsolidation, $tabContraintes, $tabContraintesMod, $tabWhere, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
		break;

	case "Nuage de points":
		NuagePoints($chaine, $tabConsolidation, $tabConsolidationMod, $nbConsolidation, $tabContraintes, $tabContraintesMod, $tabWhere, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal);
		break;
		
	default:
		echo "Pas de fonction associé a ce choix";
		break;
}

/********************************************************/
/****************LES FONCTIONS DE GRAPHES****************/
/********************************************************/

function DiagrammeBarre($requete, $tabConsolidation, $tabConsolidationMod, $nbConsolidation, $tabContraintes, $tabContraintesMod, $tabWhere, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
	//appel de la fonction de connexion à la base de donnée et on recupere les parametres voulus
	$link = connexion_Base();


	//requete recuperant les valeurs de la base de données
	$nomTable = "Departements";

	$result = mysqli_query($link, $requete) or die("selection impossible 2");

	// Definir les données
	$dataPar2 = array(); //Nom des colonnes en axe X
	$tabArray = array(); //Contient toutes les valeurs de toutes les consolidations de la requete


	//permet de remplir le tableau 
	
	$tabArray=renvoieValeurSelect($result,$tabConsolidationModOriginal,$tabConsolidationOriginal,$nbConsolidationOriginal);


	$result = mysqli_query($link, $requete) or die("selection impossible 2");

	$trouveLaEnBasLa = false;
	for ($i = 0; $i < $nbContrainte; $i++) {
		if ($tabContraintesMod[$i] == "GROUP BY") {
			while ($donnees = mysqli_fetch_assoc($result)) {
				array_push($dataPar2, $donnees["$tabContraintes[$i]"]);
			}
			$trouveLaEnBasLa = true;
		}
	};


	// Créer le graphe
	$tailleX=$nbConsolidationOriginal*1500;
	$tailleY=$nbConsolidationOriginal*700;
	
	
	$graph = new Graph($tailleX,$tailleY, 'auto');
	$graph->SetScale("textlin");

	$theme_class = new UniversalTheme;
	$graph->SetTheme($theme_class);

	$graph->SetBox(false);

	$graph->ygrid->SetFill(false);
	$graph->xaxis->SetTickLabels($dataPar2);
	$graph->yaxis->HideLine(false);
	$graph->yaxis->HideTicks(false, false);

	$tabBplot = array();

	// Create the bar plots
	for ($i = 0; $i < count($tabConsolidationOriginal); $i++) {
		$bplot = new BarPlot($tabArray[$i]);
		$bplot->SetColor("white");
		$bplot->SetFillColor("#cc1111");
		array_push($tabBplot, $bplot);
	}
	$tabFinal = new GroupBarPlot($tabBplot);

	// ...and add it to the graPH
	$graph->Add($tabFinal); 

	$graph->title->Set("Bar Plots");

	// Display the graph
	$graph->Stroke();
}

function DiagrammeSecteur($requete, $tabConsolidation, $tabConsolidationMod, $nbConsolidation, $tabContraintes, $tabContraintesMod, $tabWhere, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
	//appel de la fonction de connexion à la base de donnée et on recupere les deux parametres voulues
	$link = connexion_Base();


	//requete recuperant les valeurs de la base de données
	$nomTable = "Departements";

	$graph = new PieGraph(1200, 800);
	$graph->SetShadow();
	$theme_class = new UniversalTheme;
	$graph->SetTheme($theme_class);

	$p = array();

	// Create the plots
	for ($i = 0; $i < $nbConsolidationOriginal; ++$i) {
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
		$result = mysqli_query($link, $requeteCourante) or die("selection impossible 2");

		while ($donnees = mysqli_fetch_assoc($result)) {
			array_push($tabArray, $donnees["$tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])"]);
		}

		$d = "tabArray$i";
		//sort($tabArray[$i]);;
		$p[] = new PiePlot($tabArray);

		$p[$i]->title->Set($i);

		$p[$i]->SetStartAngle(90);

		$p[$i]->value->Show();

		$p[$i]->SetSize(0.20);
		$p[$i]->SetCenter(0.25+0.5*$i,0.5);
	}
	// Use one legend for the whole graph
	$p[0]->SetLegends($dataPar2);
	$graph->legend->SetShadow(false);

	for ($i = 0; $i < $nbConsolidationOriginal; ++$i) {
		$graph->Add($p[$i]);
	}
	$graph->Stroke();
}

function NuagePoints($chaine, $tabConsolidation, $tabConsolidationMod, $nbConsolidation, $tabContraintes, $tabContraintesMod, $tabWhere, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
	//appel de la fonction de connexion à la base de donnée et on recupere les parametres voulus
	$link = connexion_Base();


	//requete recuperant les valeurs de la base de données
	$nomTable = "Departements";

	$result = mysqli_query($link, $chaine) or die("selection impossible 2");

	// Definir les données
	$dataPar2 = array(); //Nom des colonnes en axe X
	$tabArray = array(); //Contient toutes les valeurs de toutes les consolidations de la requete


	//permet de remplir le tableau 
	
	$tabArray=renvoieValeurSelect($result,$tabConsolidationModOriginal,$tabConsolidationOriginal,$nbConsolidationOriginal);


	$result = mysqli_query($link, $chaine) or die("selection impossible 2");

	$trouveLaEnBasLa = false;
	for ($i = 0; $i < $nbContrainte; $i++) {
		if ($tabContraintesMod[$i] == "GROUP BY") {
			while ($donnees = mysqli_fetch_assoc($result)) {
				array_push($dataPar2, $donnees["$tabContraintes[$i]"]);
			}
			$trouveLaEnBasLa = true;
		}
	};


	// Créer le graphe
	$tailleX=$nbConsolidationOriginal*1500;
	$tailleY=$nbConsolidationOriginal*700;
	
	
	$graph = new Graph($tailleX,$tailleY);
	$graph->SetScale("linlin");
 
	$graph->img->SetMargin(40,40,40,40);        
	$graph->SetShadow();
 
	$graph->title->Set("A simple scatter plot");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);

	$tabCouleur=array('green','red','blue','green');
	// Create the bar plots
	for ($i = 0; $i < count($tabConsolidationOriginal); $i++) 
	{
		$tabx=array();
		for($j=0;$j<count($tabArray[$i]);$j++)
		{
				array_push($tabx,$tabArray[$i][$j]);
		}
		$tabFinal=new ScatterPlot($tabx,$dataPar2);
		$tabFinal->link->Show();
		$tabFinal->link->SetColor($tabCouleur[$i]);
		// ...and add it to the graPH
		$graph->Add($tabFinal); 
	}


	$graph->title->Set("Scatter plot");

	// Display the graph
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

function renvoieValeurSelect($requete,$tabConsolidationModOriginal,$tabConsolidationOriginal,$nbConsolidationOriginal)
{
	$nbElement=0;
	$tabArray=array();
	
	while ($donnees = mysqli_fetch_assoc($requete,)) {
		for ($i = 0; $i < $nbConsolidationOriginal; $i++) {
			$tabArray[$i][$nbElement] = $donnees["$tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])"];
		}
		$nbElement++;
	}
	
	return($tabArray);
}