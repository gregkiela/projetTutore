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
</head>

<body>
    <h3>Voici le(s) graphique(s) choisi(s)</h3>
    <?php
    foreach ($tabGraphiques as $graph) {
        echo "<img src='graphiques/" . $graph . "'>";
    }
    ?>
</body>

</html>