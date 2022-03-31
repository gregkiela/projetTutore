<?php
session_start();
$recapColonnes = $_SESSION['nomColonnes'];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Ajout des autre sources</title>
    <link rel="stylesheet" type="text/css" href="accueilCSS.css">
    <link rel="icon" type="image/x-icon" href="./icoo.ico" />
    <link rel="shortcut icon" type="image/x-icon" href="./icoo.ico" />
    <script>
        let nbURL = 1;
        let nbFichier = 0;

        function incrementValue(div) {
            if (div == "nbURL") {
                nbURL++;
                document.getElementById(div).value = nbURL;
            } else {
                nbFichier++;
                document.getElementById(div).value = nbFichier;
            }
        }

        function ajouter(div, type, nomLabel) {
            var container = document.getElementById(div);
            var label = document.createElement("label");
            var input = document.createElement("input");
            input.setAttribute("type", type);
            input.setAttribute("required", "required");
            if (type == "file") {
                input.setAttribute("accept", ".csv,.json");
                input.setAttribute("name", "fichier[]");
            } else {
                input.setAttribute("name", "url" + nbURL);
            }
            label.innerHTML = nomLabel;
            container.appendChild(label);
            container.innerHTML += " ";
            container.appendChild(input);
            container.innerHTML += "<br>";
        }

        function ajouterURL() {
            ajouter("URL", "url", "Lien URL : ");
        }

        function ajouterFichier() {
            ajouter("fichier", "file", "Fichier (json ou csv) : ")
        }
    </script>
</head>

<body>
    <div class="contenu">
        <div class="header">
            <h1>Bienvenue sur AutoGraph</h1>
            <div class="barreFixe"></div>
        </div>

        <div class="barre"></div>

        <div class="nomPage">
            <nav>
                <div class="aligner">
                    <div class="cercleExt3">
                        <h1>✔</h1>
                    </div>
                    <h1 class="petitTexte">Première source de données</h1>
                </div>
                <div class="aligner">
                    <div class="cercleExt">
                        <h1>2</h1>
                    </div>
                    <h1 class="petitTexte">Ajout des autres sources</h1>
                </div>
                <div class="aligner">
                    <div class="cercleExt2">
                        <h1>3</h1>
                    </div>
                    <h1 class="petitTexte">Création de la requête</h1>
                </div>
                <div class="aligner">
                    <div class="cercleExt2">
                        <h1>4</h1>
                    </div>
                    <h1 class="petitTexte">Les graphes</h1>
                </div>
            </nav>

            <div class="lesSelections">
                <h2>Ajouter les autres sources</h2><br>

                <form action="traitement.php" method="POST" enctype="multipart/form-data">
                    <div id="gestionParametreJoin">
                        <label>Paramètre unique pour joindre les données : </label>
                        <select required="required" name="liste">
                            <?php
                            foreach ($recapColonnes as $colonne) {
                                echo "<option value='" . $colonne . "'>" . $colonne . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="gestionURL">
                        <div id="URL">
                            <input hidden id="nbURL" name="nbURL" type="number" value=1>
                            <label>Lien URL : </label> <input type="url" name="url0" required="required"><br>
                        </div>
                    </div>
                    <div id="gestionFicher">
                        <div id="fichier">
                            <input hidden id="nbFichier" name="nbFichier" type="number" value=0>
                        </div>
                    </div>
                    <button class="valider">Valider</button>
                </form>
                <input onclick="ajouterURL();incrementValue('nbURL');" value="Ajouter URL" type="button">
                <input onclick="ajouterFichier(),incrementValue('nbFichier')" value="Ajouter fichier" type="button">
                <?php
                if (isset($_GET['erreur'])) {
                    if ($_GET['erreur'] == "mauvaisType") {
                        echo "<p>Merci de renseigner uniquement des fichiers csv ou json</p>";
                    }
                }
                ?>
            </div>
        </div>
</body>

</html>