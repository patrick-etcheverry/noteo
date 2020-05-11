//Cette fonction sert à créer un tableau au format JSON représentait une partie
function addChild(idParent, nom, bareme){
    var nomAffiche = nom;
    var nouvellePartie = {
      text: nomAffiche,
      nom: nom,
      bareme: bareme,
      id: next_id,
      state : {expanded: true},
      tags: ['/ '+ bareme],
    }
    next_id++;
    ajoutViaRechercheRecursive(treeBoot[0], idParent, nouvellePartie);
}

/*
Cette fonction sert à réaliser l'ajout d'une sous-partie à une partie (la partie est un tableau au format JSON).
La partie à laquelle on veut ajouter la nouvelle sous-partie est repérée par son identifiant.
La recherche de la partie à laquelle ajouter la nouvelle sous-partie se fait ici recursivement
dans un tableau JSON qui contient toutes les parties affichées à l'écran.
*/
function ajoutViaRechercheRecursive(partieCourante, idPartieParente, nouvellePartie){
    //Pour toutes les sous-parties de la partie courante
    for(var i = 0; i < partieCourante.nodes.length; i++){
        //Si on trouve la partie correspondante à celle à qui on veut ajouter une nouvelle sous partie
        if(partieCourante.nodes[i].id == idPartieParente){
            //Si la partie qu'on a trouvé a des sous parties on y ajoute la nouvelle
            if(partieCourante.nodes[i].nodes != undefined){
                partieCourante.nodes[i].nodes.push(nouvellePartie);
            }
            //Sinon, l'attribut n'est pas défini dans le json. On le définit alors
            else {
                partieCourante.nodes[i].nodes = [nouvellePartie];
            }

            // On vérifie que si on ajoute cette nouvelle sous-partie dans une partie, les barèmes ne sont pas incohérents. Si oui on affichera une couleur spéciale
            var totalBareme = 0;
            //Pour toutes les sous parties de la partie correspondant à idPartieParente
            for (var j = 0; j < partieCourante.nodes[i].nodes.length; j++) {
                totalBareme += partieCourante.nodes[i].nodes[j].bareme;
            }
            if (totalBareme < partieCourante.nodes[i].bareme) {
                /* Si la somme des barèmes est inférieure au barème de la partie supérieure on affiche la ligne supérieure en jaune pour avertir
                l'utilisateur. La couleur jaune a été choisie comme un avertissement pour que l'utilisateur n'oublie pas, car si le barème est inférieur
                cela peut vouloir dire qu'il est en train de constuire ses parties */
                partieCourante.nodes[i].backColor = '#f0ad4e';
                partieCourante.nodes[i].color = 'white';
            }
            else if (totalBareme > partieCourante.nodes[i].bareme) {
                /* Si la somme des barèmes est supérieure au barème de la partie supérieure on affiche la ligne supérieure en rouge pour avertir
                l'utilisateur. La couleur rouge a été choisie comme un erreur pour que l'utilisateur corrige car c'est une situation qui n'est pas censée être réalisable */
                partieCourante.nodes[i].backColor = '#d9534f';
                partieCourante.nodes[i].color = 'white';
            }
            else if (totalBareme === partieCourante.nodes[i].bareme) {
                /* Si la somme des barèmes est égale au barème de la partie supérieure on affiche la ligne normalement, sans définir de couleurs spéciales */
                partieCourante.nodes[i].backColor = undefined;
                partieCourante.nodes[i].color = undefined;
            }
            //On renvoie true pour dire que l'élément a été trouvé
            return true;
            break;
        }
        //Sinon on recherche un niveau en dessous (si il existe)
        else if (partieCourante.nodes[i].nodes != undefined){
            var partieTrouvee = ajoutViaRechercheRecursive(partieCourante.nodes[i], idPartieParente, nouvellePartie);
            //Si on l'a trouvé un niveau en dessous, plus besoin de chercher
            if(partieTrouvee){
                break;
            }
        }
    }
    //Si tous les éléments on été parcourus c'est que la partie à laquelle on voulait ajouter une sous-partie n'a pas été trouvée, on renvoie false pour le signifier
    return false;
}

//Initialisation du tableau
function ajouterEnfants(){
    addChild(2, 'Question 1', 5);
    addChild(2, 'Question 2', 6);
    addChild(3, 'Question 3', 5);
    addChild(3, 'Question 4', 4);
    $('#arbre_boot').treeview({data: treeBoot, showTags : true, expandIcon: 'fas fa-chevron-right blue', collapseIcon: 'fas fa-chevron-down blue', selectedBackColor: '#0275d8'});
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
id: next_id,
icon: 'icon-plus',
}
next_id++;
recursiveChildren(treeJS[0], parentID, new_child);
}



function recursiveChildren(root, parentID, newChild){
for(var i = 0; i < root.children.length; i++){
if(root.children[i].id == parentID){
if(root.children[i].children != undefined){
root.children[i].children.push(newChild);
} else {
root.children[i].children = [newChild];
}
return true;
break;
} else if (root.children[i].children != undefined){
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