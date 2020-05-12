
//Variables
var prochainId = 2; // Représente le prochain identifiant disponible pour les parties
var tree = [ //Données initiales -> seulement une partie représentant l'évaluation
    {
        id: 1,
        text: "Evaluation",
        nom: 'Evaluation',
        bareme: 20,
        state : {expanded: true},
        tags: ['/ '+ 20],
    }
];

//Cette fonction sert à afficher l'arbre à partir des données contenues dans le json
function chargerArbre() {
    $('#arbre_boot').treeview({data: tree, showTags : true, expandIcon: 'fas fa-chevron-right blue', collapseIcon: 'fas fa-chevron-down blue', selectedBackColor: '#0275d8'});
}
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
    //Si la partie courante a des enfants on continue la recherche
    if(partieCourante.nodes != undefined) {
        //Pour toutes les sous-parties de la partie courante
        for(var i = 0; i < partieCourante.nodes.length; i++){
            ajoutViaRechercheRecursive(partieCourante.nodes[i], idPartieParenteACelleAjoutee, nouvellePartie)
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
    //Si la partie courante a des enfants
    if(partieCourante.nodes != undefined) {
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
        //On execute la vérification pour tous les enfants de la partie courante
        for (var i = 0; i< partieCourante.nodes.length; i++) {
            checkBaremesArbre(partieCourante.nodes[i]);
        }
    }
}

