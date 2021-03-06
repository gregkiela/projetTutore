<?php

include 'fonctions.php';

$link = connexionBase();

$nomTableTotal = "total";


/*
**CREATION DES DEUX TABLEAUX
*/

$tabColonnes = array(); //tableau contenant le nom des colonnes
$tabColonnesTypes = array(); //tableau contenant le type de ces colonnes

//requete SQL permettant de recuperer le nom des colonnes et leurs types
$reponse = $link->query("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$nomTableTotal'") or die("pas de select");


//on parcours la reponse
foreach ($reponse as $donnees) {
	array_push($tabColonnesTypes, $donnees['DATA_TYPE']); //on insere le type de la colonne courante
	array_push($tabColonnes, $donnees['COLUMN_NAME']); //on insere le nom de la colonne courante
}


$tabValeur = array("=", ">", "<", "<=", ">=");
$tabModalitesConsolidationLabel = array("Le nombre de ", "La moyenne de");
$tabModalitesConsolidation = array("SUM", "AVG");
$tabModalitesContraintesLabel = array("Grouper par", "Ordonner par", "Par Valeur");
$tabModalitesContraintes = array("GROUP BY", "ORDER BY", "WHERE");
?>

<html>

<head>

	<!-------------------->
	<!---- JAVASCRIPT ---->
	<!-------------------->

	<script type="text/javascript">
		/*
		 **VARIABLES GLOBALES
		 */
		var choix; //choix de l'utilisateur concatenĂ©
		var formalisme; //choix de formalisme de l'utilisateur
		var tabColonnes = <?php echo json_encode($tabColonnes); ?>; //le tableau contenant les colonnes de la base
		var tabColonnesTypes = <?php echo json_encode($tabColonnesTypes); ?>; //le tableau contenant les types des colonnes de la base

		var tabConsolidations = []; //tableau des valeurs de la premiere ligne
		var tabContraintes = []; //tableau des valeurs de la deuxieme ligne

		var tabModalitesConsolidationLabel = <?php echo json_encode($tabModalitesConsolidationLabel); ?>;
		var tabModalitesConsolidation = <?php echo json_encode($tabModalitesConsolidation); ?>;

		var tabModalitesContraintesLabel = <?php echo json_encode($tabModalitesContraintesLabel); ?>;
		var tabModalitesContraintes = <?php echo json_encode($tabModalitesContraintes); ?>;

		var tabValeur = <?php echo json_encode($tabValeur); ?>
		/** TABLEAU NECESSAIRE AFIN DE DEFINIR LES CHOIX**/
		var tab_formalisme = ["Diagramme en barre", "Diagramme en secteur", "Nuage de points"];
		var nbConsolidation = 1; //nombre de consolidation dans le formulaire
		var nbContrainte = 1; //nombre de contrainte dans le formulaire
		var nbTour = 0;
		/*
		 **FONCTIONS
		 */

		//FONCTION UPDATECHOIX : permet de mettre a jour la liste deroulante celon le choix de l'utilisateur
		function UpdateChoix() {


			var form = document.getElementById("Saisie");

			var divConsolidation = document.getElementById("ZoneConsolidation");
			var enfantsConsolidation = divConsolidation.getElementsByTagName("SELECT");

			var divContrainte = document.getElementById("ZoneContrainte");
			var enfantsContrainte = divContrainte.getElementsByTagName("SELECT");


			tabConsolidations = effacerTableau(tabConsolidations);
			tabContraintes = effacerTableau(tabContraintes);

			//on recupere toutes les valeurs a consolider			
			for (var i = 0; i < enfantsConsolidation.length; i++) {
				const identifiant = (enfantsConsolidation[i].id).split('.');
				tabConsolidations[identifiant[1]] = enfantsConsolidation[i].value;
			}

			console.table(tabConsolidations);

			var cptAlternatif = 0;
			//on recupere toutes les contraintes
			for (var i = 0; i < enfantsContrainte.length; i++) {
				if (cptAlternatif < 2) {
					const identifiant = (enfantsContrainte[i].id).split('.');
					cptAlternatif++;
					tabContraintes[identifiant[1]] = enfantsContrainte[i].value;
				} else {
					cptAlternatif = 0;
				}
			}

			console.table(tabContraintes);

			//on verifie les contraintes pour agir sur les contraintes
			ajouterInputValeur();

			if (nbTour == 0) {
				//choix fait dans la liste de formalisme
				selection = document.getElementById("choixConsolidation"); //zone de selection de l'utilisateur

				//on creer la liste des choix de diagramme possibles
				creerChoixFormalisme(selection, tab_formalisme);

				nbTour++;
			}

		}

		//FONCTION CREERCHOIXFORMALISME : permet de remplir la liste des formalismes possible avec les elements d'un tableau passĂ© en paramĂ©tre
		function creerChoixFormalisme(liste, tab) {
			effacer(liste); //on vide la liste avant de la remplir

			for (var i = 0; i < (tab.length); i++) {
				var option = document.createElement("option"); //on creer la variable qui vas contenir toutes les options
				option.id = i;
				option.value = tab[i];
				option.text = tab[i]; //la variable d'option recupere les valeurs du tableau passĂ© en paramĂ©tre une par une
				liste.add(option); //la variable option est ajoutĂ©e en tant que telle dans la liste passĂ©e en paramĂ©tre

			}
		}
		//FONCTION CREERNOUVEAUCHOIX : permet de remplir une nouvelle liste avec un tableau de valeur
		function creerNouveauChoix(liste, tab) {
			effacer(liste); //on vide la liste avant de la remplir

			for (var i = 0; i < (tab.length); i++) {
				var option = document.createElement("option"); //on creer la variable qui vas contenir toutes les options
				option.id = i;
				option.value = i;
				option.text = tab[i]; //la variable d'option recupere les valeurs du tableau passĂ© en paramĂ©tre une par une
				liste.add(option); //la variable option est ajoutĂ©e en tant que telle dans la liste passĂ©e en paramĂ©tre
			}
		}

		//FONCTION creerNouveauChoixModalites : permet de remplir une nouvelle liste avec un tableau de valeur
		function creerNouveauChoixModalites(liste, tab, tab2) {
			effacer(liste); //on vide la liste avant de la remplir

			for (var i = 0; i < (tab.length); i++) {
				var option = document.createElement("option"); //on creer la variable qui vas contenir toutes les options
				option.id = i;
				option.value = tab2[i];
				option.text = tab[i]; //la variable d'option recupere les valeurs du tableau passĂ© en paramĂ©tre une par une
				liste.add(option); //la variable option est ajoutĂ©e en tant que telle dans la liste passĂ©e en paramĂ©tre
			}
		}
		//FONCTION EFFACER : permet de vider une liste deroulante de ces elements
		function effacer(listeP) {
			options = listeP.children; //on recupere toutes les otpions de la liste passĂ©e en paramĂ©tre
			for (var j = 0; j < (options.length) + 1; j++) {
				listeP.remove(options[j]); //on retire toutes les options de la liste
			}
		}

		//FONCTION EFFACERTABLEAU : permet de vider une liste deroulante de ces elements
		function effacerTableau(tab) {
			tab = [];
			return tab;
		}

		//FONCTION CREERGRAPHIQUE : permet de creer un graphique a partir d'une base de donnĂ©e, de deux attributs et d'un choix de formalisme
		function CreerGraphique() {
			formalisme = document.getElementById("choixConsolidation").value; //on recupere le formalisme voulue par l'utilisateur
			monUrl = "graphique.php?Consolidation0=";
			for (var cptConso = 0; cptConso < tabConsolidations.length;) {
				if (tabConsolidations[cptConso] != null) {
					var mod = document.getElementById("ConsolidationMod." + cptConso).value;
					if (cptConso != 0) {
						monUrl += "&Consolidation" + cptConso + "=" + tabColonnes[tabConsolidations[cptConso]];
					} else {
						monUrl += tabColonnes[tabConsolidations[cptConso]];
					}
					monUrl += "&ConsolidationMod" + cptConso + "=" + mod;
				}
				cptConso++;
			}
			for (var cptContr = 0; cptContr < tabContraintes.length;) {
				if (tabContraintes[cptContr] != null) {
					var mod = document.getElementById("ContrainteMod." + cptContr).value;
					monUrl += "&Contrainte" + cptContr + "=" + tabColonnes[tabContraintes[cptContr]];

					monUrl += "&ContrainteMod" + cptContr + "=" + mod;
					if (mod == "WHERE") {
						monUrl += "&Comparaison" + cptContr + "=" + tabValeur[document.getElementById("ContrainteValeur." + cptContr).value];
						monUrl += "&Valeur" + cptContr + "=" + document.getElementById("ContrainteInput." + cptContr).value;
					}
					cptContr++;
				}

			}

			monUrl += "&formalisme=" + formalisme + "&nbconsolidation=" + cptConso + "&nbContrainte=" + cptContr;
			window.open(monUrl);
			//document.getElementById("img1").src = monUrl;
			//test_valeurs_tableau(tabConsolidations,tabContraintes);
		}

		//FONCTION AJOUTERCONSOLIDATION : permet d'ajouter une zone de selection de valeur si l'utilisateur le souhaite pour la consolidation
		function AjouterConsolidation() {

			//on recupere la division
			var div = document.getElementById("ZoneConsolidation");

			//on recupere le boutton
			var bouttonAjout = document.getElementById("bouttonConsolidation");

			//on supprime le bouton
			supprimerElement(bouttonAjout, div);

			//on ajoute le boutton de suppression
			var bouttonSuppr = document.createElement("input");
			var temp = nbConsolidation;
			bouttonSuppr.value = "-";
			bouttonSuppr.type = "button";
			bouttonSuppr.id = "bouttonConsolidation." + nbConsolidation;
			bouttonSuppr.onclick = function() {
				supprimerElements(temp, "Consolidation")
			};

			//on creer le paragraphe
			var paragraphe = document.createElement("label");
			paragraphe.setAttribute("For", "ConsolidationMod." + nbConsolidation);
			paragraphe.setAttribute("id", "ParagrapheConsolidation." + nbConsolidation)
			paragraphe.innerHTML = " et ";

			//on creer le select contenant les colonnes
			var select = document.createElement("select");
			select.setAttribute("onchange", "UpdateChoix()");
			select.id = "Consolidation." + nbConsolidation;

			//on creer le select de la modalitĂ© de recupĂ©ration de la valeur
			var selectModalite = document.createElement("select");
			selectModalite.setAttribute("onchange", "UpdateChoix()");
			selectModalite.id = "ConsolidationMod." + nbConsolidation;
			nbConsolidation = nbConsolidation + 1;

			//on remplis ces selects
			creerNouveauChoix(select, tabColonnes);
			creerNouveauChoixModalites(selectModalite, tabModalitesConsolidationLabel, tabModalitesConsolidation);

			//on insert le contenue de l'ajout dans la div
			div.appendChild(paragraphe);
			div.appendChild(selectModalite);
			div.appendChild(select);

			//on rajoute les boutton
			div.appendChild(bouttonSuppr);
			div.appendChild(bouttonAjout);

			UpdateChoix();
		}

		//FONCTION AJOUTERCONTRAINTE: permet d'ajouter une zone de selection de valeur si l'utilisateur le souhaite pour les contraintes
		function AjouterContrainte() {
			//on recupere la division
			var div = document.getElementById("ZoneContrainte");

			//on recupere le boutton
			var bouttonAjout = document.getElementById("bouttonContrainte");

			//on supprime le bouton
			supprimerElement(bouttonAjout, div);

			//on creer le boutton suppr
			var bouttonSuppr = document.createElement("input");
			bouttonSuppr.value = "-";
			bouttonSuppr.type = "button";
			bouttonSuppr.id = "bouttonContrainte." + nbContrainte;
			var temp = nbContrainte;
			bouttonSuppr.onclick = function() {
				supprimerElements(temp, "Contrainte")
			};

			//on creer le paragraphe
			var paragraphe = document.createElement("label");
			paragraphe.setAttribute("For", "ContrainteMod." + nbContrainte);
			paragraphe.innerHTML = " et ";
			paragraphe.setAttribute("id", "ParagrapheContrainte." + nbContrainte);

			//on creer le select contenant les colonnes
			var select = document.createElement("select");
			select.setAttribute("onchange", "UpdateChoix()");
			select.id = "Contrainte." + nbContrainte;

			//on creer une zone d'input au cas ou on veut faire une comparaison par valeur
			var inputValeur = document.createElement("input");
			inputValeur.id = "ContrainteInput." + nbContrainte;
			inputValeur.setAttribute("disabled", "disabled");

			//on creer une liste de selection pour savoir quelle comparaison on veut effectuer
			var selectValeur = document.createElement("select");
			selectValeur.setAttribute("onchange", "UpdateChoix()");
			selectValeur.id = "ContrainteValeur." + nbContrainte;
			selectValeur.setAttribute("disabled", "disabled");

			//on creer le select de la modalitĂ© de recupĂ©ration de la valeur
			var selectModalite = document.createElement("select");
			selectModalite.setAttribute("onchange", "UpdateChoix()");
			selectModalite.id = "ContrainteMod." + nbContrainte;
			nbContrainte++;


			//on remplis ces selects
			creerNouveauChoix(selectValeur, tabValeur);
			creerNouveauChoix(select, tabColonnes);
			creerNouveauChoixModalites(selectModalite, tabModalitesContraintesLabel, tabModalitesContraintes);

			//on insert le contenue de l'ajout dans la div
			div.appendChild(paragraphe);
			div.appendChild(selectModalite);
			div.appendChild(select);

			//on rajoute le boutton
			div.appendChild(selectValeur);
			div.appendChild(inputValeur);
			div.appendChild(bouttonSuppr);
			div.appendChild(bouttonAjout);

			UpdateChoix();
		}

		//FONCTION SUPPRIMERELEMENT : permet de supprimer un element de son parent
		function supprimerElement(enfant, parent) {
			parent.removeChild(enfant);
		}

		//FONCTION SUPPRIMERELEMENT : permet de supprimer plusieurs elements d'une division
		function supprimerElements(i, chaine) {
			var div = document.getElementById("Zone" + chaine);

			var chaineGlobal = chaine + "." + i;

			var supprime = document.getElementById(chaineGlobal);
			supprimerElement(supprime, div);

			var supprime = document.getElementById("Paragraphe" + chaineGlobal);
			supprimerElement(supprime, div);

			var supprime = document.getElementById(chaine + "Mod." + i);
			supprimerElement(supprime, div);

			var supprime = document.getElementById("boutton" + chaineGlobal);
			supprimerElement(supprime, div);

			if (chaine == "Contrainte") {
				var supprime = document.getElementById("ContrainteInput." + i);
				supprimerElement(supprime, div);

				var supprime = document.getElementById("ContrainteValeur." + i);
				supprimerElement(supprime, div);
			}
			UpdateChoix();
		}

		//FONCTION AJOUTERINPUTVALEUR : ajoute un input si il faut comparer par valeur
		function ajouterInputValeur() {
			for (var compteur = 0; compteur < tabContraintes.length; compteur++) {
				if (tabContraintes[compteur] != null) {
					var mod = document.getElementById("ContrainteMod." + compteur).value;
					var inputVal = document.getElementById("ContrainteInput." + compteur);
					var bouttonVal = document.getElementById("ContrainteValeur." + compteur);

					if (mod == "WHERE") {
						inputVal.removeAttribute("disabled");
						bouttonVal.removeAttribute("disabled");
					} else {
						inputVal.setAttribute("disabled", "disabled");
						bouttonVal.setAttribute("disabled", "disabled");
					}
				}
			}
		}
		/*
		 **FONCTIONS DE TESTS
		 */
		function test_des_valeurs_courantes(p1, p2) {
			var chaine = "Parametre numero :" + p1 + " -> " + tabColonnes[p1] + " -> " + tabColonnesTypes[p1] + "<br>";
			chaine += "Parametre numero :" + p2 + " -> " + tabColonnes[p2] + " -> " + tabColonnesTypes[p2];

			document.write(chaine);
		}

		function test_valeurs_tableau(tab1, tab2) {
			for (var i = 0; i < tab1.length; i++) {
				document.write("Tab1[" + i + "] : " + tab1[i] + " <br>");
			}

			for (var i = 0; i < tab2.length; i++) {
				document.write("Tab2[" + i + "] : " + tab2[i] + " <br>");
			}

		}
	</script>

	<link rel="stylesheet" type="text/css" href="accueilCSS.css">
	<link rel="icon" type="image/x-icon" href="./icoo.ico" />
	<link rel="shortcut icon" type="image/x-icon" href="./icoo.ico" />

</head>

<!------------------->
<!-- LE FORMULAIRE -->
<!------------------->

<body onload="UpdateChoix()">

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
						<a href="Accueil.php">âœ”</a>
					</div>
					<h1 class="petitTexte">PremiĂ¨re source de donnĂ©es</h1>
				</div>
				<div class="aligner">
					<div class="cercleExt3">
						<a href="AjoutDonnees.php">âœ”</a>
					</div>
					<h1 class="petitTexte">Ajout des autres sources</h1>
				</div>
				<div class="aligner">
					<div class="cercleExt">
						<h1>3</h1>
					</div>
					<h1 class="petitTexte">CrĂ©ation de la requĂªte</h1>
				</div>
				<div class="aligner">
					<div class="cercleExt2">
						<h1>4</h1>
					</div>
					<h1 class="petitTexte">Les graphes</h1>
				</div>
			</nav>

			<div class="lesSelections">
				<!-- ZONE DE SELECTION DU PARAMETRE NUMERO 1 -->
				<form id="Saisie">

					<div id="ZoneConsolidation" name="ZoneConsolidation" class="ZoneConsolidation">
						<label for="ConsolidationMod.0"> Je veux consolider : </label>
						<select name="ConsolidationMod.0" id="ConsolidationMod.0" onchange="UpdateChoix()">

							<?php
							$cpt = 0;
							while ($cpt < count($tabModalitesConsolidationLabel)) {
								echo "<option value='$tabModalitesConsolidation[$cpt]'>" . $tabModalitesConsolidationLabel[$cpt] . "</option>";
								$cpt++;
							}
							?>
						</select>
						<select name="Consolidation.0" id="Consolidation.0" onchange="UpdateChoix()">";

							<?php
							$cpt = 0;
							while ($cpt < count($tabColonnes)) {
								echo "<option value='$cpt'>" . $tabColonnes[$cpt] . "</option>";
								$cpt++;
							}
							?>
						</select>

						<!-- BOUTTON QUI AJOUTE UNE POSSIBILITE-->
						<button type="button" name="bouttonConsolidation" id="bouttonConsolidation" onClick="AjouterConsolidation()">+</button>
					</div>

					<!-- ZONE DE SELECTION DU PARAMETRE NUMERO 2 -->
					<div name="ZoneContrainte" id="ZoneContrainte">
						<label for="ContrainteMod.0"> En fonction de : </label>
						<select name="ContrainteMod.0" id="ContrainteMod.0" onchange="UpdateChoix()">";

							<?php
							$cpt = 0;
							while ($cpt < count($tabModalitesContraintesLabel)) {
								echo "<option value='$tabModalitesContraintes[$cpt]'>" . $tabModalitesContraintesLabel[$cpt] . "</option>";
								$cpt++;
							}
							?>
						</select>
						<select name="Contrainte.0" id="Contrainte.0" onchange="UpdateChoix()">";

							<?php
							$cpt = 0;
							while ($cpt < count($tabColonnes)) {
								echo "<option value='$cpt'>" . $tabColonnes[$cpt] . "</option>";
								$cpt++;
							}
							?>
						</select>
						<select name="ContrainteValeur.0" id="ContrainteValeur.0" onchange="UpdateChoix()">";

							<?php
							$cpt = 0;
							while ($cpt < count($tabValeur)) {
								echo "<option value='$cpt'>" . $tabValeur[$cpt] . "</option>";
								$cpt++;
							}
							?>
						</select>
						<input name="ContrainteInput.0" id="ContrainteInput.0"></input>
						<!-- BOUTTON QUI AJOUTE UNE POSSIBILITE-->
						<button type="button" name="bouttonContrainte" id="bouttonContrainte" onClick="AjouterContrainte()">+</button>
					</div>

				</form>

				<!-- ZONE DE SELECTION DE LA CONSOLIDATION VOULUE -->
				<div name="zoneChoix">
					<select name="choixConsolidation" id="choixConsolidation">
					</select>
				</div>

				<!-- BOUTTON PERMETTANT DE CREER UN GRAPHIQUE CELON LA VALEUR CHOISIE-->
				<div name="boutton">
					<button type="button" onClick="CreerGraphique()">CONSOLIDER !</button>
				</div>
				<br>
				<?php
				if (isset($_GET['erreur'])) {
					echo "<p><font color='pink'>Attention, votre sĂ©lection est incohĂ©rente, veuillez rĂ©essayer</font></p>";
				}
				?>
			</div>
		</div>

</body>

</html>