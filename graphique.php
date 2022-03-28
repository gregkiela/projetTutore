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
$nbConsolidation = $_GET['nbconsolidation'];//le nombre de select a faire
$nbConsolidationOriginal= $_GET['nbconsolidation'];
$nbContrainte = $_GET['nbContrainte'];//le nombre de WHERE/GROUP BY/ORDER BY a faire
$nbContrainteOriginal = $_GET['nbContrainte'];
$formalisme = $_GET['formalisme'];//le formalisme souhaité
$nomTable = "Departements";//le nom de la table de la base de donnée

$variableJointure="code";

$tabConsolidation= array();//contient toutes les recherches du select
$tabConsolidationMod = array();//contient les modalités de recherche du select
$tabConsolidationOriginal= array();
$tabConsolidationModOriginal= array();
$tabContraintes= array();//contient toutes les variables soumisent a contrainte
$tabContraintesMod= array();//contient les modalités de ces contraintes
$tabWhere=array();//contient la comparaison des comparaison "where"
$tabWhereValeur=array();

//on recupere toutes les consolidations
for($i=0;$i<$nbConsolidation;$i++)
{
	array_push($tabConsolidation,$_GET['Consolidation'.$i]);
	array_push($tabConsolidationMod,$_GET['ConsolidationMod'.$i]);
	array_push($tabConsolidationOriginal,$_GET['Consolidation'.$i]);
	array_push($tabConsolidationModOriginal,$_GET['ConsolidationMod'.$i]);
}

$possedeUnGroupBy=false;
$insertDeVariableDansSelect=0;

//on recupere toutes les contraintes
for($i=0;$i<$nbContrainte;$i++)
{
	array_push($tabContraintes,$_GET['Contrainte'.$i]);
	array_push($tabContraintesMod,$_GET['ContrainteMod'.$i]);
	//si la contrainte est un where
	if($tabContraintesMod[$i]=="WHERE")
	{
		$tabWhere[$i]=$_GET['Comparaison'.$i];
		$tabWhereValeur[$i]=$_GET['Valeur'.$i];//on ajoute au tableau la maniére de comapraison
	}
	else if($tabContraintesMod[$i]=="GROUP BY")
	{
		$possedeUnGroupBy=true;
		$groupByEstDansWhere=false;
		for($j=0;$j<$nbConsolidation;$j++)
		{
			if($tabConsolidation[$j]==$tabContraintes[$i])
			{
				$groupByEstDansWhere=true;
			}
		}
		if(!$groupByEstDansWhere)
		{
			array_push($tabConsolidation,$tabContraintes[$i]);
			array_push($tabConsolidationMod," ");
			$nbConsolidation++;
		}
	}
}

//on démarre la séléction
$chaine="SELECT ";

//on concaténe toutes les recherches du select avec leurs modes de recherche et on intégre dans le tableau des GROUP BY les attributs du select qui n'y sont pas
for($i=0;$i<$nbConsolidation;$i++)
{
	if($tabConsolidationMod[$i]==" ")
	{
		$chaine.="$tabConsolidation[$i]";
	}
	else
	{
		$chaine.="$tabConsolidationMod[$i]($tabConsolidation[$i])";
	}
	if($i<$nbConsolidation-1)
	{
		$chaine.=",";
	}
	if($possedeUnGroupBy)
	{
		//on intégre dans le tableau des GROUP BY les attributs du select qui n'y sont pas
		$trouveGroupBy=false;
		for($j=0;$j<$nbContrainte;$j++)
		{
			if($tabContraintesMod[$j]=="GROUP BY" && $tabContraintes[$j]==$tabConsolidation[$i])
			{
				$trouveGroupBy=true;
				$j=$nbContrainte;
			}
		}
	
		if(!$trouveGroupBy)
		{
			array_push($tabContraintes,$tabConsolidation[$i]);
			array_push($tabContraintesMod,"GROUP BY");
			$nbContrainte++;
		}
	}
}



//on ajoute le from à la requete
$chaine.=" FROM $nomTable ";

//on ajoute tout les where
$nbWhere=0;

//on parcours le tableau de contraintes
for($i=0;$i<$nbContrainte;$i++)
{
	//si la contrainte est un where
	if($tabContraintesMod[$i]=="WHERE")
	{
		//on la concaténe avec son mode de comparaison
		if($nbWhere==0)
		{
			$chaine.="WHERE $tabContraintes[$i] $tabWhere[$i] \"$tabWhereValeur[$i]\"";
		}
		else
		{
			$chaine.=" AND $tabContraintes[$i] $tabWhere[$i] \"$tabWhereValeur[$i]\""." ";
		}
		$nbWhere++;
	}
}

//on ajoute tout les group by
$nbGroupBy=0;

//on parcours toutes les contraintes
for($i=0;$i<$nbContrainte;$i++)
{
	//si la contrainte est un group by
	if($tabContraintesMod[$i]=="GROUP BY")
	{
		//on la concaténe avec sa variable attitrée
		if($nbGroupBy==0)
		{
			$chaine.=" GROUP BY $tabContraintes[$i]";
		}
		else
		{
			$chaine.=",$tabContraintes[$i]";
		}
		$nbGroupBy++;
	}
}

//on ajoute tout les orders by
$nbOrderBy=0;

//on parcours le tableau
for($i=0;$i<$nbContrainte;$i++)
{
	//si la contrainte est un order by
	if($tabContraintesMod[$i]=="ORDER BY")
	{
		//on la concaténe a la requete avec sa variable attitrée
		if($nbOrderBy==0)
		{
			$chaine.=" ORDER BY $tabContraintes[$i]";
		}
		else
		{
			$chaine.=",$tabContraintes[$i]";
		}
		$nbOrderBy++;
	}
}

$chaine.=";";


switch ($formalisme) {
	case "Diagramme en secteur":
		DiagrammeSecteur($chaine,$tabConsolidation,$tabConsolidationMod,$nbConsolidation,$tabContraintes,$tabContraintesMod,$tabWhere,$nbContrainte,$tabConsolidationOriginal,$tabConsolidationModOriginal,$nbConsolidationOriginal);
		break;

	case "Diagramme en barre":
		DiagrammeBarre($chaine,$tabConsolidation,$tabConsolidationMod,$nbConsolidation,$tabContraintes,$tabContraintesMod,$tabWhere,$nbContrainte,$tabConsolidationOriginal,$tabConsolidationModOriginal,$nbConsolidationOriginal);
		break;

	default:
		echo "Pas de fonction associé a ce choix";
		break;
}

/********************************************************/
/****************LES FONCTIONS DE GRAPHES****************/
/********************************************************/

function DiagrammeBarre($requete,$tabConsolidation,$tabConsolidationMod,$nbConsolidation,$tabContraintes,$tabContraintesMod,$tabWhere,$nbContrainte,$tabConsolidationOriginal,$tabConsolidationModOriginal,$nbConsolidationOriginal)
{
	//appel de la fonction de connexion à la base de donnée et on recupere les parametres voulus
	$link = connexion_Base();

	
	//requete recuperant les valeurs de la base de données
	$nomTable = "Departements";

	$result = mysqli_query($link, $requete) or die("selection impossible 2");

	// Definir les données
	$dataPar1 = array();
	$dataPar2 = array();

	$tabArray= array();
	$tabX=array();
	$tabY=array();
	
	$nbElement=0;
	while ($donnees = mysqli_fetch_assoc($result)) 
	{
		for($i=0;$i<$nbConsolidationOriginal;$i++)
		{
			$tabArray[$i][$nbElement]=$donnees["$tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])"];
		}
		$nbElement++;
	}
	
	$result = mysqli_query($link, $requete) or die("selection impossible 2");
	
	$trouveLaEnBasLa=false;
	for($i=0;$i<$nbContrainte;$i++)
	{
		if($tabContraintesMod[$i]=="GROUP BY")
		{
			while ($donnees = mysqli_fetch_assoc($result)) 
			{
				array_push($dataPar2,$donnees["$tabContraintes[$i]"]);
			}
			$trouveLaEnBasLa=true;
		}
	}
	
	for($i=15,$j=0;$i<$nbElement;$i=$i+30,$j=$j+30)
	{
		array_push($tabX,$i);
		array_push($tabY,$j);
	}
	
	;
	// Créer le graphe
	$graph = new Graph(10000,1000,'auto');
	$graph->SetScale("textlin");

	$theme_class=new UniversalTheme;
	$graph->SetTheme($theme_class);

	$graph->yaxis->SetTickPositions($tabX,$tabY);
	$graph->SetBox(false);

	$graph->ygrid->SetFill(false);
	$graph->xaxis->SetTickLabels($dataPar2);
	$graph->yaxis->HideLine(false);
	$graph->yaxis->HideTicks(false,false);

	// Create the bar plots
	$b1plot = new BarPlot($tabArray[0]);
	$b2plot = new BarPlot($tabArray[1]);

	$tabFinal= new GroupBarPlot(array($b1plot,$b2plot));
	
	// ...and add it to the graPH
	$graph->Add($tabFinal);

	
	$b1plot->SetColor("white");
	$b1plot->SetFillColor("#cc1111");

	$b2plot->SetColor("white");
	$b2plot->SetFillColor("#11cccc");


	$graph->title->Set("Bar Plots");

	// Display the graph
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

