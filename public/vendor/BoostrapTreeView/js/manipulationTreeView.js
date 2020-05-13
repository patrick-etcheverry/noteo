/***********VARIABLES***********/
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

/***********FONCTIONS***********/
/*
Cette fonction sert à parcourir le tableau contenant les différentes parties de manière récursive.
Elle sert à y retrouver une partie particulière (identifiée par un identifiant numérique), et à executer différentes actions
en fonction du contexte :
    - Ajouter une sous partie (la nouvelle sous-partie est en paramètre de la fonction)
    - Modifier la partie (la partie qui la remplacera avec les nouvelles informations est en paramètre de la fonction)
    - Supprimer la partie (La partie à supprimer est dans les paramètres. On cherche son parent pour supprimer la partie des enfants de son parent)
*/
function actionViaRechercheRecursive(partieCourante, idPartieCherchee, partieSurLaquelleAgir = [], action){
    //On vérifie si la partie courante est celle à laquelle on veut ajouter une partie
    if(partieCourante.id == idPartieCherchee){
        switch (action) {
            case "modifier" :
                break;
            case "supprimer" :
                break;
            case "ajouter" :
                //On ajoute la partie dans l'arbre, sous la partie trouvée
                effectuerLajoutDansLarbre(partieCourante, partieSurLaquelleAgir);
                break;
        }

        //On renvoie true pour dire que l'action sur la partie voulue a été effectuée
        return true;
    }
    //Si la partie courante a des enfants on continue la recherche
    if(partieCourante.nodes != undefined) {
        //Pour toutes les sous-parties de la partie courante
        for(var i = 0; i < partieCourante.nodes.length; i++){
            actionViaRechercheRecursive(partieCourante.nodes[i], idPartieCherchee, partieSurLaquelleAgir, action)
        }
    }
    //Si toutes les parties on été parcourues c'est que la partie à laquelle on voulait ajouter une sous-partie n'a pas été trouvée, on renvoie false pour le signifier
    return false;
}

//Cette fonction sert à créer un tableau au format JSON représentant une partie et à déclencher l'ajout dans le tableau
function ajouterUnePartie(idParent, nom = "Partie", bareme = 5){
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
    actionViaRechercheRecursive(tree[0], idParent, nouvellePartie, "ajouter");
    chargerArbre()
}

function effectuerLajoutDansLarbre(partie, nouvelleSousPartie) {
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
            partieCourante.icon = 'icon-attention-circled red';
            partieCourante.text = partieCourante.nom + ' - Somme des points incorrecte'
            partieCourante.color = '#FF0000';
            //$('#message-erreur-parties').append('<p><i class="icon-attention-circled"></i> Le total des barèmes pour ' + partieCourante.nom + ' doit être égal à ' + partieCourante.bareme + '</p>');
        }
        else if (totalBareme === partieCourante.bareme) {
            /* Si la somme des barèmes est égale au barème de la partie supérieure on affiche la ligne normalement, sans définir de couleurs spéciales */
            partieCourante.icon = undefined;
            partieCourante.text = partieCourante.nom;
            partieCourante.color = undefined;
        }
        //On execute la vérification pour tous les enfants de la partie courante
        for (var i = 0; i< partieCourante.nodes.length; i++) {
            checkBaremesArbre(partieCourante.nodes[i]);
        }
    }
}

//Cette fonction sert à afficher l'arbre à partir des données contenues dans le json
function chargerArbre() {
    $('#arbre_boot').treeview({data: tree, showTags : true, expandIcon: 'fas fa-chevron-right blue', collapseIcon: 'fas fa-chevron-down blue', selectedBackColor: '#0275d8'});
}

