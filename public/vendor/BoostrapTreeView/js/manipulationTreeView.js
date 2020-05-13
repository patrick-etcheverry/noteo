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
    if(partieCourante.id == idPartieCherchee){
        switch (action) {
            case "modifier" :
                effectuerLaModificationDansLarbre(partieCourante, partieSurLaquelleAgir)
                break;
            case "supprimer" :
                effectuerLaSuppressionDansLarbre(partieCourante, partieSurLaquelleAgir);
                break;
            case "ajouter" :
                effectuerLajoutDansLarbre(partieCourante, partieSurLaquelleAgir);
                break;
        }
        return true;
    }
    if(partieCourante.nodes != undefined) {
        for(var i = 0; i < partieCourante.nodes.length; i++){
            actionViaRechercheRecursive(partieCourante.nodes[i], idPartieCherchee, partieSurLaquelleAgir, action)
        }
    }
    return false;
}

/*
Cette fonction sert à créer un tableau représentant une partie et à déclencher la recherche de l'endroit où l'insérer
*/
function ajouterUnePartie(idParent, nom = "Partie", bareme = 5){
    var nomAffiche = nom;
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

/*
Cette fonction sert à effectuer l'ajout d'une nouvelle partie dans le tableau des parties, et ensuite déclencher la vérification de la cohérence des barèmes
*/
function effectuerLajoutDansLarbre(partie, nouvelleSousPartie) {
    if(partie.nodes != undefined){
        partie.nodes.push(nouvelleSousPartie);
    }
    else {
        partie.nodes = [nouvelleSousPartie];
    }
    checkBaremesArbre(tree[0]);
}

/*
Cette fonction sert à déclencher la suppression d'une partie en lancant la recherche de l'endroit d'où la supprimer.
*/
function supprimerUnePartie(idParent, partieASupprimer) {
    actionViaRechercheRecursive(tree[0], idParent, partieASupprimer, "supprimer");
    chargerArbre();
}

/*
Cette fonction sert à réaliser la suppression dans l'arbre à partir du parent d'une partie à supprimer
*/
function effectuerLaSuppressionDansLarbre(parent, partieASupprimer) {
    var positionDeLaPartieASupprimer = parent.nodes.findIndex(partie => partie.id == partieASupprimer.id);
    parent.nodes.splice(positionDeLaPartieASupprimer,1)
    checkBaremesArbre(tree[0]);
}

/*
Cette fonction sert à créer un tableau représentant les nouvelles informations d'une partie à modifier et à déclencher la recherche de l'ancienne pour remplacer les informations
*/
function modifierUnePartie(idPartie, nouveauNom, nouveauBareme) {
    var nomAffiche = nouveauNom;
    var partieModifiee = {
        text: nomAffiche,
        nom: nouveauNom,
        bareme: nouveauBareme,
        tags: ['/ '+ nouveauBareme],
    }
    actionViaRechercheRecursive(tree[0], idPartie, partieModifiee, "modifier");
    chargerArbre();
}

/*
Cette fonction sert à réaliser la suppression dans l'arbre à partir du parent d'une partie à supprimer
*/
function effectuerLaModificationDansLarbre(partie, nouvellesInfos) {
    partie.text = nouvellesInfos.text;
    partie.nom = nouvellesInfos.nom;
    partie.bareme = nouvellesInfos.bareme;
    partie.tags = nouvellesInfos.tags;
    checkBaremesArbre(tree[0]);
}

/*
Cette fonction sert à vérifier la cohérence des barèmes dans l'arbre de manière récursive.
Pour chaque partie, elle vérifiera que son barème est égal à la somme des barèmes de ses sous parties directes, si elle en a.
*/
function checkBaremesArbre(partieCourante) {
    if(partieCourante.nodes != undefined) {
        var totalBareme = 0;
        for (var i = 0; i < partieCourante.nodes.length; i++) {
            totalBareme += partieCourante.nodes[i].bareme;
        }
        if (totalBareme != partieCourante.bareme) {
            partieCourante.icon = 'icon-attention-circled red';
            partieCourante.text = partieCourante.nom + ' - Somme des points incorrecte'
            partieCourante.color = '#FF0000';
        }
        else if (totalBareme === partieCourante.bareme) {
            partieCourante.icon = undefined;
            partieCourante.text = partieCourante.nom;
            partieCourante.color = undefined;
        }
        for (var i = 0; i< partieCourante.nodes.length; i++) {
            checkBaremesArbre(partieCourante.nodes[i]);
        }
    }
}

/*
Cette fonction charge l'arbre affiché par la bibliothèque à partir des données des parties.
Elle initialise également l'état des boutons de modification et suppression et ajoute des écouteurs d'évenements pour pouvoir les désactiver si on clique sur la partie racine
*/
function chargerArbre() {
    $('#boutonModifier').unbind()
    $('#boutonSupprimer').unbind()
    $('#boutonModifier').prop('disabled', false)
    $('#boutonSupprimer').prop('disabled', false)
    $('#arbre_boot').treeview({
        data: tree,
        showTags : true,
        expandIcon: 'fas fa-chevron-right blue',
        collapseIcon: 'fas fa-chevron-down blue',
        selectedBackColor: '#0275d8',
        onNodeSelected: function(event, data) {
            if(data.id == 1) {
                $('#boutonModifier').prop('disabled', true)
                $('#boutonSupprimer').prop('disabled', true)
            }
        },
        onNodeUnselected: function(event, data) {
            $('#boutonModifier').prop('disabled', false)
            $('#boutonSupprimer').prop('disabled', false)
        }
    });
}

