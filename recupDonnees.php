<?php
session_start();
$recapColonnes = $_SESSION['nomColonnes'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>wewewe</title>
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
            ajouter("URL", "url", "URL données");
        }

        function ajouterFichier() {
            ajouter("fichier", "file", "Fichier Données (json ou csv)")
        }
    </script>
</head>

<body>
    <h3>Vos données ont bien été enregistrés</h3>
    <h4>Veuillez maintenant remplir ce formulaire</h4>
    <h4>C:\wamp64\www\Projets\ProjetTutoré\bailleurs.json</h4>

    <form action="traitement.php" method="POST" enctype="multipart/form-data">
        <div id="gestionParametreJoin">
            <label>Choix du paramètre unique pour joindre vos données</label>
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
                <label>URL données</label> <input type="url" name="url0" required="required"><br>
            </div>
        </div>
        <div id="gestionFicher">
            <div id="fichier">
                <input hidden id="nbFichier" name="nbFichier" type="number" value=0>
            </div>
        </div>
        <button>Valider</button>
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
</body>
</html>