<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Premières données</title>
    <link rel="stylesheet" type="text/css" href="accueilCSS.css">
    <script>
        var cpt = 0;
        function inputEnFonction(choix) {
            var container = document.getElementById("divChoixTypeSourceDonnees");
            if (cpt > 0) {
                var input = document.getElementById('input');
                input.parentNode.removeChild(input);
                var label = document.getElementById('labelChoix');
                label.parentNode.removeChild(label);
                var submit = document.getElementById('submit');
                submit.parentNode.removeChild(submit);
            }
            var label = document.createElement("label");
            label.setAttribute("id", "labelChoix");
            var input = document.createElement("input");
            input.setAttribute("id", "input");
            var submit = document.createElement("input");
            submit.setAttribute("id", "submit");

            switch (choix) {
                case "file":
                    input.setAttribute("type", choix);
                    input.setAttribute("accept", ".csv,.json,.xml");
                    break;
                default:
                    input.setAttribute("type", choix);
                    break;
            }
            input.setAttribute("name", "nom");
            input.setAttribute("required", "required");

            if(choix=="file"){
                label.innerHTML = "Fichier : ";
            }
            else{
                label.innerHTML = "URL : ";
            }

            submit.setAttribute("type", "submit");

            container.appendChild(label);
            container.innerHTML += " ";
            container.appendChild(input);
            container.appendChild(submit);
            cpt++;
        }
    </script>
</head>

<body>
    <div class="contenu">
        <div class="header">
			<h1>Bienvenue sur AutoGraph</h1>
		</div>

        <div class="barre"></div>

        <div class="nomPage">
            <nav> 
                <div class="aligner">
                    <div class="cercleExt">
                        <h1>1</h1>
                    </div>
                    <h1 class="petitTexte">Première source de données</h1>
                </div>
                <div class="aligner">
                    <div class="cercleExt2">
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
                <h3>CSV : https://www.data.gouv.fr/fr/datasets/r/f146c6a7-e215-4214-a250-399c6309f9a0</h3>
                <h3>JSON : Bailleurs -> C:\wamp64\www\Projets\ProjetTutoré\bailleur.json</h3>
                <h3>JSON : COVID -> C:\wamp64\www\Projets\ProjetTutoré\COVID.json</h3>
                <h3>TXT : https://www.data.gouv.fr/fr/datasets/r/5129e7cf-2999-4eaf-8dd7-3bcda37bd0a3</h3>
                <h3>XML : https://www.data.gouv.fr/fr/datasets/r/1e31b17b-c467-43a1-b0a9-20fc862911f1</h3>
                <form action="premierTraitement.php" method="POST" enctype="multipart/form-data">
                    <label>Quelle est la source des données ?</label>
                    <select name="choixTypeSourceDonnees" id="choixTypeSourceDonnees">
                        <option value="url">URL</option>
                        <option value="file">Fichier JSON/CSV/XML</option>
                    </select>
                    <input onclick="inputEnFonction(choixTypeSourceDonnees.options[choixTypeSourceDonnees.selectedIndex].value)" type="button" value="Valider">
                    <br>
                    <div id="divChoixTypeSourceDonnees">
                    </div>
                    <?php
                    if (isset($_GET['erreur'])) {
                        if ($_GET['erreur'] == "mauvaisType") {
                            echo "<p>Merci de renseigner uniquement des fichiers csv, json ou xml</p>";
                        }
                        if ($_GET['erreur'] == "mauvaiseURL") {
                            echo "<p>Attention, le lien renseigné pose un problème</p>";
                        }
                    }
                    ?>
                </form>
        </div>
    </div>
</body>
</html>