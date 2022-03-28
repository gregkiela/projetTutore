<?php
function typeValeur($type)
{
    $typeBD = "";
    switch ($type) {
        case 'integer':
            $typeBD = "FLOAT";
            break;
        case 'double':
            $typeBD = "FLOAT";
            break;
        case 'string':
            $typeBD = "TEXT";
            break;
        case 'NULL':
            $typeBD = "TEXT";
            break;
    }
    return $typeBD;
}

function multiarray_keys($tableau)
{
    $keys = array();
    foreach ($tableau as $key => $v) {
        if (is_array($tableau[$key])) {
            $keys = array_merge($keys, multiarray_keys($tableau[$key]));
        } else {
            $keys[] = $key;
        }
    }
    return $keys;
}

function multiarray_values($tableau)
{
    $values = array();
    foreach ($tableau as $key => $v) {

        if (is_array($tableau[$key])) {
            $values = array_merge($values, multiarray_values($tableau[$key]));
        } else {
            $values[] = $v;
        }
    }
    return $values;
}

function csvToJson($fname)
{
    // open csv file
    if (!($fp = fopen($fname, 'r'))) {
        die("Can't open file...");
    }
    //read csv headers
    $key = fgetcsv($fp, "1024", ";");
    // parse csv rows into array
    $json = array();
    while ($row = fgetcsv($fp, "1024", ";")) {
        $json[] = array_combine($key, $row);
    }
    // release file handle
    fclose($fp);
    // encode array to json
    return json_encode($json);
}

function connexionBase()
{
    //connexion à la base de données
    $link = mysqli_connect("localhost", "root", "", "gerrecart") or die("Impossible de se connecter");

    $requete = 'SET NAMES UTF8';
    mysqli_query($link, $requete);

    return $link;
}

function gestionDonneeRecu()
{
    if (!empty($_FILES)) {
        $type = $_FILES['nom']['type'];
        if ($type != "application/vnd.ms-excel" && $type != "application/json" && $type != "application/xml") {
            header("Location: recupPremiereDonnee.php?erreur=mauvaisType");
        }
        $contenu = file_get_contents($_FILES['nom']['tmp_name']);
        $json = json_decode($contenu, true);
    } else {
        $urlRecue = $_POST['nom'];
        $contenu = file_get_contents($urlRecue);
        $json = json_decode($contenu, true);
    }

    return array($json, $contenu);
}

function actionSurJson($json, $contenu)
{
    if (empty($json)) {
        $formatCSV = strpos($contenu, ';');
        if ($formatCSV < 50 && $formatCSV !== false) {
            $json = csvToJson($_POST['nom']);
            $json = json_decode($json, true);
        } else {
            $xml = simplexml_load_string($contenu);
            $json = json_encode($xml);
            $json = json_decode($json, true);
            $json = $json[array_key_first($json)];
        }
    }

    return $json;
}

function verifTable($lienBD, $nomTable)
{
    $requete = "SHOW TABLES LIKE '$nomTable'";
    $result = mysqli_query($lienBD, $requete);
    $tableExists = mysqli_num_rows($result) > 0;
    if ($tableExists) {
        $requete = "DROP TABLE $nomTable";
        mysqli_query($lienBD, $requete);
    }
}

function creationTable($lienBD, $nomTable, $cles, $valeurs, $indice)
{
    $requete =  "CREATE TABLE $nomTable (";
    $i = 0;

    foreach ($cles as $cle) {
        if (++$i === count($cles)) {
            $finSuiteRequete = ")";
        } else {
            $finSuiteRequete = ",";
        }
        $suiteRequete = " " . $cle . " " . typeValeur(gettype($valeurs[$indice][$i - 1])) . $finSuiteRequete;
        $requete = $requete . $suiteRequete;
    }
    $requete = $requete . " ENGINE = InnoDB DEFAULT CHARSET utf8 DEFAULT COLLATE utf8_unicode_ci;";
    echo $requete;
    mysqli_query($lienBD, $requete) or die("Impossible de créer la table");
}

function clesPresentFichier($json)
{
    $indice = 0;
    $clesMax = array();
    $plusGrand = 0;
    foreach ($json as $key => $obj) {
        $nbCourant = count(multiarray_keys($obj));
        if ($nbCourant > $plusGrand) {
            $plusGrand = $nbCourant;
            $clesMax = multiarray_keys($obj);
            $indice = $key;
        }
    }

    //Création du tableau donnant l'information pour chaque objet sur le fait qu'il ait la clé ou non
    $clesToutObjets = array();

    foreach ($json as $obj) {
        //Tableau infos clés objet courant
        $present = array();

        //clés de l'objet courant
        $clesObjet = multiarray_keys($obj);

        for ($i = 0, $j = 0; $i < $plusGrand; $i++, $j++) {
            if ($clesMax[$i] == $clesObjet[$j]) {
                array_push($present, true);
            } else {
                array_push($present, false);
                $j--;
            }
        }
        array_push($clesToutObjets, $present);
    }

    return array($clesToutObjets, $clesMax, $plusGrand, $indice);
}

function valeursObjetsJson($json, $presenceCles, $nombreMaxCles)
{
    //Création du tableau indiquant les valeurs associés au futurs clés
    $valeursToutObjets = array();

    //Boucle sur le json complet
    for ($i = 0; $i < count($json); $i++) {
        //Je stock les valeurs de l'objet courant du json
        $valeursObjet = multiarray_values($json[$i]);

        //Tableau valeurs finales
        $valeursFinalesObjet = array();

        for ($j = 0, $y = 0; $j < $nombreMaxCles; $j++, $y++) {
            if ($presenceCles[$i][$j] == true) {
                array_push($valeursFinalesObjet, $valeursObjet[$y]);
            } else {
                array_push($valeursFinalesObjet, NULL);
                $y--;
            }
        }
        array_push($valeursToutObjets, $valeursFinalesObjet);
    }

    return $valeursToutObjets;
}

function insertionValeurs($lienBD, $nomTable, $valeurs)
{
    foreach ($valeurs as $valObj) {
        //Déclaration début requête
        $requete =  "INSERT INTO $nomTable VALUES(";

        $i = 0;
        foreach ($valObj as $valeur) {
            if (++$i === count($valObj)) {
                $finSuiteRequete = ");";
            } else {
                $finSuiteRequete = ",";
            }
            if (typeValeur(gettype($valeur)) == "FLOAT") {
                $suiteRequete = " " . str_replace('"', '\'', $valeur) . " " . $finSuiteRequete;
            } else {
                if (is_null($valeur)) {
                    $suiteRequete = 'NULL' . $finSuiteRequete;
                } else {
                    $suiteRequete = '"' . str_replace('"', '\'', $valeur) . '"' . $finSuiteRequete;
                }
            }
            $requete = $requete . $suiteRequete;
        }
        mysqli_query($lienBD, $requete) or die("Impossible d'insérer les données");
    }
}

function recupNomColonnes($lienBD, $nomTable)
{
    //Maintenant on va répérer le nom des colonnes via une requète
    $requete = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$nomTable'";
    $nomColonnes = mysqli_query($lienBD, $requete) or die("Impossible de récupérer le nom des colonnes");

    $stockNomColonnes = array();

    foreach ($nomColonnes as $colonne) {
        array_push($stockNomColonnes, $colonne['COLUMN_NAME']);
    }

    return $stockNomColonnes;
}