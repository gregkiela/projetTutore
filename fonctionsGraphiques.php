<?php

function renvoieValeurSelect($requete, $tabConsolidationModOriginal, $tabConsolidationOriginal, $nbConsolidationOriginal)
{
    $nbElement = 0;
    $valeursRequete = array();

    while ($donnees = mysqli_fetch_assoc($requete)) {
        for ($i = 0; $i < $nbConsolidationOriginal; $i++) {
            $valeursRequete[$i][$nbElement] = $donnees["$tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])"];
        }
        $nbElement++;
    }

    return ($valeursRequete);
}

function agregationToTitre($agregation, $type = false)
{
    $titre = "";
    switch ($agregation) {
        case 'SUM':
            if (!$type) {
                $titre = "Le nombre de ";
            } else {
                $titre = "Répartition de ";
            }
            break;
        case 'AVG':
            if (!$type) {
                $titre = "La moyenne de ";
            } else {
                $titre = "Répartition de la moyenne de ";
            }
            break;
        case 'COUNT':
            if (!$type) {
                $titre = "Evocation de ";
            } else {
                $titre = "Répartition de l'évocation de ";
            }
            break;
    }
    return $titre;
}

function tableauCouleurs()
{
    return array(
        '#61a9f3',
        '#f381b9',
        '#61E3A9',
        '#D56DE2',
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

function DiagrammeBarre($requete, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
    //appel de la fonction de connexion à la base de donnée et on recupere les parametres voulus
    $link = connexionBase();
    $result = mysqli_query($link, $requete) or die("selection impossible 2");

    // Definir les données
    $valeursLegende = array(); //Nom des colonnes en axe X
    $valeursRequete = array(); //Contient toutes les valeurs de toutes les consolidations de la requete

    //permet de remplir le tableau 
    $valeursRequete = renvoieValeurSelect($result, $tabConsolidationModOriginal, $tabConsolidationOriginal, $nbConsolidationOriginal);

    $result = mysqli_query($link, $requete) or die("selection impossible 2");

    for ($i = 0; $i < $nbContrainte; $i++) {
        if ($tabContraintesMod[$i] == "GROUP BY") {
            while ($donnees = mysqli_fetch_assoc($result)) {
                array_push($valeursLegende, $donnees["$tabContraintes[$i]"]);
            }
        }
    };

    // Créer le graphe
    $largeurGraphique = $nbConsolidationOriginal * 1800;
    $hauteurGraphique = $nbConsolidationOriginal * 1200;

    $graph = new Graph($largeurGraphique, $hauteurGraphique, 'auto');
    $graph->SetScale("textlin");

    $graph->graph_theme = null;

    $graph->SetMargin(60, 40, 40, 200);
    $graph->SetBox(false);

    //Axe des abcisses
    $graph->xaxis->SetTickLabels($valeursLegende);
    if(count($valeursLegende)>10)
    {
        $graph->xaxis->SetLabelAngle(90);
    }
    $graph->xaxis->title->Set(ucfirst($tabContraintes[0]));

    //Axe des ordonnées
    $graph->yaxis->HideTicks(true, false);

    $groupeDePlot = array();

    $titre = "";
    // Create the bar plots
    for ($i = 0; $i < count($tabConsolidationOriginal); $i++) {
        //Création du titre
        if ($i == 0) {
            $titre .= agregationToTitre($tabConsolidationModOriginal[$i]) . $tabConsolidationOriginal[$i];
        }
        //On ne met que le début de la phrase avec une majuscule, sinon minuscule
        else {
            $titre .= strtolower(agregationToTitre($tabConsolidationModOriginal[$i])) . $tabConsolidationOriginal[$i];
        }
        if ($i != count($tabConsolidationOriginal) - 1) {
            $titre .= " et ";
        }
        $bplot = new BarPlot($valeursRequete[$i]);
        $bplot->SetColor("white");
        $bplot->SetFillColor(tableauCouleurs()[$i]);
        $bplot->value->Show();
        $bplot->value->HideZero();
        $bplot->value->SetColor(tableauCouleurs()[$i]);
        $bplot->value->SetFormat('%01.0f');
        array_push($groupeDePlot, $bplot);
    }
    $groupeDePlot = new GroupBarPlot($groupeDePlot);

    $graph->Add($groupeDePlot);

    $titre .= " par " . $tabContraintes[0];

    $graph->title->Set($titre);

    $fileName = "graphiques/imagefile.png";
    $graph->Stroke($fileName);
}

function DiagrammeSecteur($requete, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
    //appel de la fonction de connexion à la base de donnée et on recupere les deux parametres voulues
    $link = connexionBase();

    //Pour chaque select de l'utilisateur
    for ($i = 0; $i < $nbConsolidationOriginal; ++$i) {

        //Création du graphique
        $graph = new PieGraph(1200, 800);

        //on définit le theme à null
        $graph->graph_theme = null;

        //Création du titre
        $titre = agregationToTitre($tabConsolidationModOriginal[$i], true);
        $titre = $titre . $tabConsolidationOriginal[$i];
        $titre .= " par " . $tabContraintes[0];
        $graph->title->Set($titre);

        $valeursLegende = array(); //Nom des colonnes en axe X
        $valeursRequete = array(); //Contient toutes les valeurs de toutes les consolidations de la requete

        //Création de la requete
        $requeteCourante = $requete;
        for ($j = 0; $j < $nbContrainte; $j++) {
            if ($tabContraintesMod[$j] == "GROUP BY") {
                //On  rajoute nous meme le order by pour afficher le graphique selon les règles des diagrammes en secteur
                $requeteCourante .= " ORDER BY $tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])";
                $result = mysqli_query($link, $requeteCourante) or die("selection impossible 2");
                while ($donnees = mysqli_fetch_assoc($result)) {
                    array_push($valeursLegende, $donnees["$tabContraintes[$j]"]);
                }
            }
        }

        $result = mysqli_query($link, $requeteCourante) or die("selection impossible 2");

        while ($donnees = mysqli_fetch_assoc($result)) {
            array_push($valeursRequete, $donnees["$tabConsolidationModOriginal[$i]($tabConsolidationOriginal[$i])"]);
        }

        $pie = new PiePlot($valeursRequete);

        //On définit l'angle de départ pour qu'on parte bien du haut du graphique
        $pie->SetStartAngle(90);

        $pie->SetSliceColors(array(
            '#CFE7FB',
            '#F9D76F',
            '#B9D566',
            '#FFBB90',
            '#66BBBB',
            '#E69090',
            '#BB90BB',
            '#9AB67C',
            '#D1CC66',



            '#AFD8F8',
            '#F6BD0F',
            '#8BBA00',
            '#FF8E46',
            '#008E8E',

            '#D64646',
            '#8E468E',
            '#588526',
            '#B3AA00',
            '#008ED6',

            '#9D080D',
            '#A186BE',

        ));

        //Définition de la légende du graphique
        $pie->SetLegends($valeursLegende);
        $graph->legend->SetFrameWeight(1);

        $graph->Add($pie);

        $fileName = "graphiques/imagefile$i.png";

        $graph->Stroke($fileName);
    }
}

function NuagePoints($chaine, $tabContraintes, $tabContraintesMod, $nbContrainte, $tabConsolidationOriginal, $tabConsolidationModOriginal, $nbConsolidationOriginal)
{
    //on récupère le parametre de connexion à la bd  
    $link = connexionBase();

    //récupération résultats de la requête
    $result = mysqli_query($link, $chaine) or die("selection impossible 2");

    // Definir les données
    $valeursLegende = array(); //Nom des colonnes en axe X
    $valeursRequete = array(); //Contient toutes les valeurs de toutes les consolidations de la requete

    //permet de remplir le tableau
    $valeursRequete = renvoieValeurSelect($result, $tabConsolidationModOriginal, $tabConsolidationOriginal, $nbConsolidationOriginal);


    //On rerécupère les valeurs de la requete 
    $result = mysqli_query($link, $chaine) or die("selection impossible 2");
    for ($i = 0; $i < $nbContrainte; $i++) {
        if ($tabContraintesMod[$i] == "GROUP BY") {
            while ($donnees = mysqli_fetch_assoc($result)) {
                array_push($valeursLegende, $donnees["$tabContraintes[$i]"]);
            }
        }
    };

    // Créer le graphe
    $largeurGraphique = 900;
    $hauteurGraphique = 600;


    $graph = new Graph($largeurGraphique, $hauteurGraphique);
    $graph->SetScale("intlin");

    $graph->graph_theme = null;

    $graph->img->SetMargin(50, 40, 40, 90);
    $graph->SetBox(false);

    //Axe des abcisses
    $graph->xaxis->SetTitle(ucfirst($tabContraintes[0]));
    $graph->xaxis->SetTitleMargin(5);

    //Axe des ordonnées
    $graph->yaxis->HideTicks(true, false);

    //Créer les tracés du graphique et le titre
    $titre = '';
    for ($i = 0; $i < count($tabConsolidationOriginal); $i++) {
        //Création du titre
        if ($i == 0) {
            $titre .= agregationToTitre($tabConsolidationModOriginal[$i]) . $tabConsolidationOriginal[$i];
        }
        //On ne met que le début de la phrase avec une majuscule, sinon minuscule
        else {
            $titre .= strtolower(agregationToTitre($tabConsolidationModOriginal[$i])) . $tabConsolidationOriginal[$i];
        }
        if ($i != count($tabConsolidationOriginal) - 1) {
            $titre .= " et ";
        }

        //Remplissage du tableau qui contiendra les valeurs du graphiques
        $valeursGraphique = array();
        for ($j = 0; $j < count($valeursRequete[$i]); $j++) {
            //Grace à la requete
            array_push($valeursGraphique, $valeursRequete[$i][$j]);
        }

        //Modification des valeurs du graphiques
        //On modifie les valeurs null
        foreach ($valeursGraphique as &$valeur) {
            if ($valeur == null) {
                $valeur = '';
            }
        }

        $points = new ScatterPlot($valeursGraphique, $valeursLegende);
        //Personnalisation des points
        $points->mark->SetType(6);
        $points->mark->SetFillColor(tableauCouleurs()[$i]);
        $points->mark->SetWidth(3);

        //Ajout de la légende pour les points
        $points->SetLegend($tabConsolidationOriginal[$i]);

        //On ajoute les points au graphique
        $graph->Add($points);
    }

    //Personnalisation de la légende
    $graph->legend->SetFrameWeight(1);
    $graph->legend->SetHColMargin(10);
    $graph->legend->SetPos(0.5, 0.94, 'center', 'top');

    $titre .= " par " . $tabContraintes[0];

    $graph->title->Set($titre);
    $graph->title->SetFont(FF_FONT2, FS_BOLD);


    // Display the graph
    $fileName = "graphiques/imagefile.png";
    $graph->Stroke($fileName);
}
