//Cette fonction sert à créer un tableau au format JSON représentant une partie et à déclencher l'ajout dans le tableau
function ajoutEnfant(idParent, nom, bareme){
    var nomAffiche = nom; // Le nom qui sera visible par l'utilisateur
    var nouvellePartie = {
      text: nomAffiche,
      nom: nom,
      bareme: bareme,
      id: prochainId,
      state : {expanded: true},
      tags: ['/ '+ bareme],
    }
    prochainId++;
    ajoutViaRechercheRecursive(tree[0], idParent, nouvellePartie);
}

/*
Cette fonction sert à réaliser l'ajout d'une sous-partie à une partie (la partie est un tableau au format JSON).
La partie à laquelle on veut ajouter la nouvelle sous-partie est repérée par son identifiant.
La recherche de la partie à laquelle ajouter la nouvelle sous-partie se fait ici recursivement
dans un tableau JSON qui contient toutes les parties affichées à l'écran.
*/
function ajoutViaRechercheRecursive(partieCourante, idPartieParenteACelleAjoutee, nouvellePartie){
    //On vérifie si la partie courante est celle à laquelle on veut ajouter une partie
    if(partieCourante.id == idPartieParenteACelleAjoutee){
        //On ajoute la partie dans l'arbre, sous la partie trouvée
        ajoutDansLarbre(partieCourante, nouvellePartie)
        //On renvoie true pour dire que la partie a été trouvée
        return true;
    }
    //Pour toutes les sous-parties de la partie courante
    for(var i = 0; i < partieCourante.nodes.length; i++){
        //Si on trouve la partie correspondante à celle à qui on veut ajouter une nouvelle sous partie
        if(partieCourante.nodes[i].id == idPartieParenteACelleAjoutee){
            //On ajoute la partie dans l'arbre, sous la partie trouvée
            ajoutDansLarbre(partieCourante.nodes[i], nouvellePartie);
            //On renvoie true pour dire que l'élément a été trouvé
            return true;
            break;
        }
        //Sinon on recherche un niveau en dessous (si il existe)
        else if (partieCourante.nodes[i].nodes != undefined){
            var partieTrouvee = ajoutViaRechercheRecursive(partieCourante.nodes[i], idPartieParenteACelleAjoutee, nouvellePartie);
            //Si on l'a trouvé un niveau en dessous, plus besoin de chercher
            if(partieTrouvee){
                break;
            }
        }
    }
    //Si toutes les parties on été parcourues c'est que la partie à laquelle on voulait ajouter une sous-partie n'a pas été trouvée, on renvoie false pour le signifier
    return false;
}

function ajoutDansLarbre(partie, nouvelleSousPartie) {
    //Si la partie qu'on a trouvé a des sous parties on y ajoute la nouvelle
    if(partie.nodes != undefined){
        partie.nodes.push(nouvelleSousPartie);
    }
    //Sinon, l'attribut n'est pas défini dans le json. On le définit alors
    else {
        partie.nodes = [nouvelleSousPartie];
    }
    $('#message-erreur-parties').empty();
    checkBaremesArbre(tree[0]);
}

function checkBaremesArbre(partieCourante) {
    //On calcule la somme des barèmes des enfants
    var totalBareme = 0;
    for (var i = 0; i < partieCourante.nodes.length; i++) {
        totalBareme += partieCourante.nodes[i].bareme;
    }
    if (totalBareme != partieCourante.bareme) {
        /* Si la somme des barèmes n'est pas égale au barème de la partie supérieure on affiche la ligne supérieure en rouge pour avertir
        l'utilisateur. La couleur rouge a été choisie comme un erreur pour que l'utilisateur corrige car c'est une situation qui n'est pas censée être réalisable */
        partieCourante.backColor = '#d9534f';
        partieCourante.color = 'white';//<i class="icon-attention-circled"></i>Le barème est erroné
        $('#message-erreur-parties').append('<p><i class="icon-attention-circled"></i> Le total des barèmes pour ' + partieCourante.nom + ' doit être égal à ' + partieCourante.bareme + '</p>');
    }
    else if (totalBareme === partieCourante.bareme) {
        /* Si la somme des barèmes est égale au barème de la partie supérieure on affiche la ligne normalement, sans définir de couleurs spéciales */
        partieCourante.backColor = undefined;
        partieCourante.color = undefined;
    }
    //On execute la vérification pour tous les enfants de la partie courante, si elle en a
    for (var i = 0; i< partieCourante.nodes.length; i++) {
        if(partieCourante.nodes[i].nodes != undefined) {
            checkBaremesArbre(partieCourante.nodes[i]);
        }
    }
}

//Initialisation du tableau
function ajouterEnfants(){
    ajoutEnfant(1, 'Exercice 1', 10); //4
    ajoutEnfant(4, 'Question 1', 5); //5
    ajoutEnfant(4, 'Question 2', 5); //6
    ajoutEnfant(1, 'Exercice 2', 10); //7
    ajoutEnfant(7, 'Question 1', 5); //8
    ajoutEnfant(7, 'Question 2', 5); //9
    //Chargement de l'arbre
    $('#arbre_boot').treeview({data: tree, showTags : true, expandIcon: 'fas fa-chevron-right blue', collapseIcon: 'fas fa-chevron-down blue', selectedBackColor: '#0275d8'});
}



//--- JSTREE ---

function addChildJSTREE(parentID, nom, bareme){
    var text = nom + " (/" + bareme + ")";
    var new_child = {
    text: text,
    nom: nom,
    bareme: bareme,
    state: {
    opened: true,
    },
    id: prochainId,
    icon: 'icon-plus',
    }
    prochainId++;
    recursiveChildren(treeJS[0], parentID, new_child);
}

function recursiveChildren(root, parentID, newChild){
    for(var i = 0; i < root.children.length; i++){
        if(root.children[i].id == parentID){
            if(root.children[i].children != undefined){
                root.children[i].children.push(newChild);
            }
            else {
                root.children[i].children = [newChild];
            }
            return true;
            break;
        }
        else if (root.children[i].children != undefined){
            sucre = root.children[i].children;
            var retour = recursiveChildren(root.children[i], parentID, newChild);
            if(retour){
                break;
            }
        }
    }
    return false;
}

function ajoutEnfants(){
    addChildJSTREE(1, 'exo3', 3);
    addChildJSTREE(3, 'exo4', 1);
    addChildJSTREE(4, 'exo5', 1);
    addChildJSTREE(5, 'exo6', 3);
    addChildJSTREE(6, 'exo7', 3);
    addChildJSTREE(7, 'exo8', 3);
    $('#arbre_js').jstree({ 'core' : { data : treeJS, check_callback: true}, 'plugins':['dnd','contextmenu']});
}