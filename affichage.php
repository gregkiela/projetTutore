<?php
$tabGraphiques = array();

$nomDossier = "graphiques/";
$dossier = opendir($nomDossier);

while ($fichier = readdir($dossier)) {
    if ($fichier != "." && $fichier != "..") {
        array_push($tabGraphiques, $fichier);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>AutoGraph</title>
    <link rel="stylesheet" type="text/css" href="accueilCSS.css">
    <link rel="icon" type="image/x-icon" href="./icoo.ico" />
    <link rel="shortcut icon" type="image/x-icon" href="./icoo.ico" />
</head>

<body>
<div  class="contenu">
        <div class="header">
			<h1>Bienvenue sur AutoGraph</h1>
		</div>

        <div class="barre"></div>

        <div class="nomPage">
            <nav> 
                <div class="aligner">
                    <div class="cercleExt3">
                        <a href="Accueil.php">✔</a>
                    </div>
                    <h1 class="petitTexte">Première source de données</h1>
                </div>
                <div class="aligner">
                    <div class="cercleExt3">
                        <a href="AjoutDonnees.php">✔</a>
                    </div>
                    <h1 class="petitTexte">Ajout des autres sources</h1>
                </div>
                <div class="aligner">
                    <div class="cercleExt3">
                        <a href="CreerRequete.php">✔</a>
                    </div>
                    <h1 class="petitTexte">Création de la requête</h1>
                </div>
                <div class="aligner">
                    <div class="cercleExt">
                        <h1>4</h1>
                    </div>
                    <h1 class="petitTexte">Les graphes</h1>
                </div>
            </nav>
    <br><br><h2>Voici le(s) graphique(s)</h2>
    <?php
    foreach ($tabGraphiques as $graph) {
        echo "<br>";
        echo "<div class='graph'>";
        echo "<img src='graphiques/" . $graph . "' width=1200>";
        echo "<br><br>";
        echo "</div>";
    }
    ?>
</body>

</html>